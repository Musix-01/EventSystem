@extends('layouts.app')
@section('title', 'Add faculty')

@section('content')
    <div class="page-head">
        <div>
            <h1>Add faculty</h1>
            <p>Faculty sign in with their PUP email. They can view events and print attendance sheets.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('admin.users.faculty.store') }}" novalidate>
        @csrf

        <div class="field {{ $errors->has('name') ? 'has-error' : '' }}">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="off" autofocus required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
            <label for="email">PUP email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="name@pup.edu.ph" autocomplete="off" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
            <label for="password">Temporary password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required>
            @error('password')
                <div class="error">{{ $message }}</div>
            @else
                <div class="hint">At least 8 characters. Share it with them privately.</div>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn">Create faculty account</button>
    </form>
@endsection
