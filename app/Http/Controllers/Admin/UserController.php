<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Organization;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role   = in_array($request->query('role'), ['student', 'officer', 'faculty'], true) ? $request->query('role') : null;
        $org    = Organization::tryFrom((string) $request->query('org', ''));

        $users = User::query()
            ->where('role', '!=', Role::Admin->value)
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($org, fn ($q) => $q->where('organization', $org->value))
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
            'organizations' => Organization::cases(),
        ]);
    }

    /** Promote a student to officer, or move an officer back to student. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStudent() || $user->isOfficer(), 403, 'Only student and officer roles can be changed here.');

        $data = $request->validate([
            'role' => ['required', Rule::in([Role::Student->value, Role::Officer->value])],
        ]);

        $user->update(['role' => $data['role']]);

        $message = $data['role'] === Role::Officer->value
            ? "{$user->name} is now a/an {$user->organization->value} officer."
            : "{$user->name} has been removed.";

        return back()->with('status', $message);
    }

    public function createFaculty(): View
    {
        return view('admin.users.faculty-create');
    }

    /** Faculty accounts are made by the admin, so nobody can sign up as faculty on their own. */
    public function storeFaculty(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
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

    /** Show a student's COR. Admin only (route is behind the admin role middleware). */
    public function cor(User $user): StreamedResponse
    {
        abort_unless($user->cor_path && Storage::disk('local')->exists($user->cor_path), 404, 'No COR on file.');

        return Storage::disk('local')->response($user->cor_path);
    }
}
