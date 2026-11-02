@extends('layouts.auth')
@section('title', 'Admin sign in')

@section('brand')
    <h1>Review requests and keep track of campus events.</h1>
    <p>Approve or decline event requests from student organizations and monitor attendance across campus.</p>
@endsection

@section('content')
    <h2>Admin sign in</h2>
    <p class="sub">For PUP office accounts only.</p>

    <form method="POST" action="{{ route('admin.login') }}" novalidate>
        @csrf

        <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" value="{{ old('email') }}"
                   placeholder="admin@pup.edu.ph" autocomplete="username" autofocus required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <label class="check">
            <input type="checkbox" name="remember" value="1"> Keep me logged in
        </label>

        <button type="submit" class="btn">Sign in</button>
    </form>

    <p class="switch">Student or org officer? <a href="{{ route('login') }}">Log in with your student number</a></p>
@endsection
