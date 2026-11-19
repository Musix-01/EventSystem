<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Organization;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Rules\StrongPassword;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role   = in_array($request->query('role'), ['student', 'officer', 'faculty'], true) ? $request->query('role') : null;
        $org    = Organization::tryFrom((string) $request->query('org', ''));
        $verify = $request->query('verify') === 'pending';

        $users = User::query()
            ->where('role', '!=', Role::Admin->value)
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($org, fn ($q) => $q->where('organization', $org->value))
            ->when($verify, fn ($q) => $q->whereIn('role', [Role::Student->value, Role::Officer->value])->whereNull('cor_verified_at'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $counts = User::query()
            ->where('role', '!=', Role::Admin->value)
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.users.index', [
            'users'         => $users,
            'counts'        => $counts,
            'search'        => $search,
            'role'          => $role,
            'org'           => $org,
            'verify'        => $verify,
            'pendingVerification' => User::whereIn('role', [Role::Student->value, Role::Officer->value])->whereNull('cor_verified_at')->count(),
            'organizations' => Organization::cases(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStudent() || $user->isOfficer(), 403, 'Only student and officer roles can be changed here.');

        $data = $request->validate([
            'role' => ['required', Rule::in([Role::Student->value, Role::Officer->value])],
        ]);

        $changes = ['role' => $data['role']];

        if ($data['role'] === Role::Student->value
            && $user->organization === Organization::SC
            && $user->academic_organization) {
            $changes['organization']          = $user->academic_organization->value;
            $changes['academic_organization'] = null;
        }

        $user->update($changes);

        $message = $data['role'] === Role::Officer->value
            ? "{$user->name} is now a/an {$user->organization->value} officer."
            : "{$user->name} has been removed.";

        return back()->with('status', $message);
    }


    public function makeStudentCouncil(User $user): RedirectResponse
    {
        abort_unless($user->isStudent() || $user->isOfficer(), 403, 'Only student accounts can become Student Council officers.');

        if ($user->organization === Organization::SC) {
            return back()->with('error', "{$user->name} is already a Student Council officer.");
        }

        $user->update([
            'academic_organization' => $user->organization?->value,
            'organization'          => Organization::SC->value,
            'role'                  => Role::Officer->value,
        ]);

        return back()->with('status', "{$user->name} is now a Student Council officer.");
    }

    public function verifyCor(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStudent() || $user->isOfficer(), 403, 'Only students and officers have a COR.');

        $verified = $request->boolean('verified');

        $user->update([
            'cor_verified_at' => $verified ? now() : null,
            'cor_verified_by' => $verified ? $request->user()->id : null,
        ]);

        return back()->with('status', $verified
            ? "{$user->name}'s COR is verified. They can join events now."
            : "{$user->name}'s COR is marked as not verified.");
    }

    public function resetPassword(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403, 'Admin passwords are changed from the profile page.');

        $temporary = $this->temporaryPassword();

        $user->forceFill([
            'password'             => $temporary,
            'must_change_password' => true,
            'remember_token'       => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        return back()->with('temp_password', [
            'name'     => $user->name,
            'login'    => $user->student_number ?? $user->email,
            'password' => $temporary,
        ]);
    }


    private function temporaryPassword(): string
    {
        $sets  = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789'];
        $chars = array_map(fn (string $set) => $set[random_int(0, strlen($set) - 1)], $sets);
        $all   = implode('', $sets);

        while (count($chars) < 10) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin() || $user->is($request->user()), 403, 'Admin accounts and your own account cannot be deleted here.');

        $corPath = $user->cor_path;
        $user->delete();

        if ($corPath && !Storage::disk('local')->delete($corPath)) {
            report(new RuntimeException("Could not delete COR file [{$corPath}] for removed user [{$user->id}]."));

            return back()->with('error', "{$user->name} was removed, but their COR file could not be deleted.");
        }

        return back()->with('status', "{$user->name} was removed.");
    }

    public function createFaculty(): View
    {
        return view('admin.users.faculty-create');
    }

    public function storeFaculty(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'email.unique' => 'An account with this email already exists.',
        ]);

        User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'student_number' => null,
            'organization'   => null,
            'role'           => Role::Faculty->value,
            'password'       => $data['password'],
        ]);

        return redirect()->route('admin.users.index', ['role' => 'faculty'])
            ->with('status', "Faculty account created for {$data['name']}.");
    }

    public function cor(User $user): StreamedResponse
    {
        abort_unless($user->cor_path && Storage::disk('local')->exists($user->cor_path), 404, 'No COR on file.');

        return Storage::disk('local')->response($user->cor_path);
    }
}
