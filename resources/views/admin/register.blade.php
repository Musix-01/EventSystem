@extends('layouts.auth')
@section('title', 'Create account')

@section('content')
    <h2>Create your account</h2>
    <p class="sub">Just a few steps to get started.</p>

    <form method="POST" action="{{ url('/register') }}" novalidate>
        @csrf

        <div class="field {{ $errors->has('name') ? 'has-error' : '' }}">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   autocomplete="name" autofocus required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('student_number') ? 'has-error' : '' }}">
            <label for="student_number">Student number</label>
            <input type="text" id="student_number" name="student_number"
                   value="{{ old('student_number') }}" placeholder="2024-00123-SR-0"
                   autocomplete="username" required>
            @error('student_number')
                <div class="error">{{ $message }}</div>
            @else
                <div class="hint">Format: 4 digits, dash, 5 digits, dash, two letters, dash, one digit.</div>
            @enderror
        </div>

        <div class="field {{ $errors->has('organization') ? 'has-error' : '' }}">
            <label for="organization">Academic organization</label>
            <select id="organization" name="organization" required>
                <option value="" disabled @selected(! old('organization'))>Choose your organization</option>
                @foreach ($organizations as $org)
                    <option value="{{ $org->value }}" @selected(old('organization') === $org->value)>
                        {{ $org->value }}
                    </option>
                @endforeach
            </select>
            @error('organization') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required>
            @error('password')
                <div class="error">{{ $message }}</div>
            @else
                <div class="hint">At least 8 characters.</div>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn">Create account</button>
    </form>

    <p class="switch">Already registered? <a href="{{ route('login') }}">Log in</a></p>
@endsection
