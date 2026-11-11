@extends('layouts.auth')
@section('title', 'Log in')

@section('brand')
    <h1>{{ $portal['brand'] }}</h1>
@endsection

@section('content')
    <h2>Log in</h2>
    <p class="sub">
        @if ($portal['by'] === 'email') Use your PUP email and password.
        @else Enter your student number and password. @endif
    </p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <input type="hidden" name="as" value="{{ $as }}">

        <div class="field {{ $errors->has('login') ? 'has-error' : '' }}">
            @if ($portal['by'] === 'email')
                <label for="login">PUP email</label>
                <input type="email" id="login" name="login" value="{{ old('login') }}"
                       placeholder="name@pup.edu.ph" autocomplete="username" autofocus required>
            @else
                <label for="login">Student number</label>
                <input type="text" id="login" name="login" value="{{ old('login') }}"
                       placeholder="2024-00123-SR-0" autocomplete="username" autofocus required>
            @endif
            @error('login') <div class="error">{{ $message }}</div> @enderror
        </div>

            <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

        <label class="check">
            <input type="checkbox" name="remember" value="1"> Keep me logged in
        </label>

        <label class="check">
            <input type="checkbox" data-show-password="password"> Show password
        </label>

        <button type="submit" class="btn">Log in</button>
    </form>

    @if ($as === 'student')
        <p class="switch">No account yet? <a href="{{ route('register') }}">Create one</a></p>
    @elseif ($as === 'officer')
        <p class="switch">No account yet? <a href="{{ route('register') }}">Create one</a></p>
    @elseif ($as === 'faculty')
    @endif

    <link rel="stylesheet" href="{{ asset('css/password.css') }}">
    <script src="{{ asset('js/password.js') }}"></script>
@endsection
