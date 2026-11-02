<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Organization;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'organizations' => Organization::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Normalize before validating so "202400123-sr-0" and "202400123-SR-0" are the same.
        $request->merge([
            'student_number' => strtoupper(trim($request->input('student_number', ''))),
        ]);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'student_number' => ['required', 'regex:/^\d{4}-\d{5}-[A-Z]{2}-\d$/', 'unique:users,student_number'],
            'organization'   => ['required', Rule::in(Organization::values())],
            'password'       => ['required', 'confirmed', Password::min(8)],
        ], [
            'student_number.regex'  => 'Use the format 2024-00123-SR-0.',
            'student_number.unique' => 'This student number is already registered.',
        ]);

        $user = User::create([
            'name'           => $data['name'],
            'student_number' => $data['student_number'],
            'organization'   => $data['organization'],
            'role'           => Role::Student->value, // everyone starts as a student; admins promote later
            'password'       => $data['password'],    // hashed by the model cast
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
