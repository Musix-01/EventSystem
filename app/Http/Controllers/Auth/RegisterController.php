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
use App\Rules\StrongPassword;
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
        // Normalize before validating so "2024-00123-sr-0" and "2024-00123-SR-0" are the same.
        $request->merge([
            'student_number' => strtoupper(trim($request->input('student_number', ''))),
        ]);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'student_number' => ['required', 'regex:/^\d{4}-\d{5}-[A-Z]{2}-\d$/', 'unique:users,student_number'],
            'organization'   => ['required', Rule::in(Organization::values())],
            'cor'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'password'       => ['required', 'string', 'confirmed', new StrongPassword()],
        ], [
            'student_number.regex'  => 'Use the format 2024-00123-SR-0.',
            'student_number.unique' => 'This student number is already registered.',
            'cor.required'          => 'Upload your Certificate of Registration (COR).',
            'cor.mimes'             => 'The COR must be a PDF, JPG or PNG file.',
            'cor.max'               => 'The COR must be 5 MB or smaller.',
            'cor.uploaded'          => 'The COR could not be uploaded. Try a smaller file (up to 5 MB).',
        ]);

        // Private disk: the file is not reachable by URL, only through an admin-only route.
        $corPath = $request->file('cor')->store('cor', 'local');

        $user = User::create([
            'name'           => $data['name'],
            'student_number' => $data['student_number'],
            'organization'   => $data['organization'],
            'role'           => Role::Student->value, // everyone starts as a student; the admin promotes officers
            'password'       => $data['password'],    // hashed by the model cast
            'cor_path'       => $corPath,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
