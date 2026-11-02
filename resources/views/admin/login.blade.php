@extends('layouts.auth')
@section('title', 'Log in')

@section('content')
    <h2>Log in</h2>
    <p class="sub">Students and organization officers sign in with a student number.</p>

    <form method="POST" action="{{ url('/login') }}" novalidate>
        @csrf

        <div class="field {{ $errors->has('student_number') ? 'has-error' : '' }}">
            <label for="student_number">Student number</label>
            <input type="text" id="student_number" name="student_number"
                   value="{{ old('student_number') }}" placeholder="2024-00123-SR-0"
                   autocomplete="username" autofocus required>
            @error('student_number') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <label class="check">
            <input type="checkbox" name="remember" value="1"> Keep me logged in
        </label>

        <button type="submit" class="btn">Log in</button>
    </form>

    <p class="switch">No account yet? <a href="{{ route('register') }}">Create one</a></p>
    <p class="switch">PUP office staff? <a href="{{ route('admin.login') }}">Admin sign in</a></p>
@endsection
