<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Login for students and student org officers (student number + password). */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'student_number' => strtoupper(trim($request->input('student_number', ''))),
        ]);

        $credentials = $request->validate([
            'student_number' => ['required', 'string'],
            'password'       => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'student_number' => 'Student number or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
    /** Shared by both login pages. Sends admins back to the admin login. */
    public function destroy(Request $request): RedirectResponse
    {
        $wasAdmin = (bool) $request->user()?->isAdmin();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasAdmin ? 'admin.login' : 'login');
    }
}
