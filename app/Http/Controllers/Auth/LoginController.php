<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The welcome page asks "who is logging in?", then each choice opens this login.
 *  - student, officer: sign in with a student number
 *  - faculty (and PUP office admins): sign in with an email
 * The account's role must match the option picked.
 */
class LoginController extends Controller
{
    public static function portals(): array
    {
        return [
            'student' => [
                'roles' => [Role::Student],
                'by'    => 'student_number',
                'title' => 'Student',
                'brand' => 'Join the events your org is hosting.',
            ],
            'officer' => [
                'roles' => [Role::Officer],
                'by'    => 'student_number',
                'title' => 'Organization officer',
                'brand' => 'Request events and keep track of who showed up.',
            ],
            'faculty' => [
                'roles' => [Role::Faculty, Role::Admin],   // PUP office admins sign in here too
                'by'    => 'email',
                'title' => 'Faculty',
                'brand' => 'Keep track of campus events and attendance.',
            ],
        ];
    }

    public function create(Request $request): View|RedirectResponse
    {
        $key = (string) $request->query('as', '');

        if (! isset(self::portals()[$key])) {
            return redirect()->route('welcome');
        }

        return view('auth.login', ['as' => $key, 'portal' => self::portals()[$key]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $portals = self::portals();
        $key     = (string) $request->input('as', '');

        if (! isset($portals[$key])) {
            return redirect()->route('welcome');
        }

        $portal = $portals[$key];

        $data = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);

        $credentials = $portal['by'] === 'email'
            ? ['email' => strtolower($login), 'password' => $data['password']]
            : ['student_number' => strtoupper($login), 'password' => $data['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => $portal['by'] === 'email'
                    ? 'Email or password is incorrect.'
                    : 'Student number or password is incorrect.',
            ]);
        }

        // Right password, but picked the wrong option (a student choosing "Faculty", an officer choosing "Student", ...)
        $user = Auth::user();

        if (! in_array($user->role, $portal['roles'], true)) {
            $actual = collect($portals)->first(fn ($p) => in_array($user->role, $p['roles'], true))['title'];
            Auth::logout();

            throw ValidationException::withMessages([
                'login' => "This account is registered as: {$actual}. Go back and choose that option to sign in.",
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }
}
