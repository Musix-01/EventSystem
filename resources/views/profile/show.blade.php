@extends('layouts.app')
@section('title', 'My profile')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/password.css') }}">

    <div class="page-head">
        <div>
            <h1>My profile</h1>
        </div>
    </div>

    @if ($user->must_change_password)
        <div class="verify-note" role="alert">
            <strong>Choose a new password to continue.</strong>
            The admin gave you a temporary password. Enter it as your current password below, then pick your own.
        </div>
    @endif

    <div class="profile-grid">
        <div class="profile-col">
            <section class="panel">
                <div class="panel-head"><h2>Account</h2></div>
                <dl class="kv">
                    <div><dt>Name</dt><dd>{{ $user->name }}</dd></div>
                    <div><dt>{{ $user->student_number ? 'Student number' : 'Email' }}</dt><dd>{{ $user->student_number ?? $user->email }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $user->affiliation() }}</dd></div>
                    @if ($user->academic_organization)
                        <div><dt>Academic org</dt><dd>{{ $user->academic_organization->value }}</dd></div>
                    @endif
                    @if ($user->year_section)
                        <div><dt>Year and section</dt><dd>{{ $user->year_section }}</dd></div>
                    @endif
                </dl>
            </section>

            @unless ($user->isStaff())
                <section class="panel">
                    <div class="panel-head"><h2>Year and section</h2></div>
                    <p class="muted-note" style="margin-top:0">This fills in the form when you join an event.</p>
                    <form method="POST" action="{{ route('profile.class') }}" class="profile-form" novalidate>
                        @csrf @method('PUT')
                        <div class="yearsec-row">
                            <div class="field {{ $errors->has('program') ? 'has-error' : '' }}">
                                <label for="program">Program</label>
                                <select id="program" name="program" required>
                                    <option value="" disabled @selected(! old('program', $prefill['program']))>Select</option>
                                    @foreach (config('campus.programs') as $p)
                                        <option value="{{ $p }}" @selected(old('program', $prefill['program']) === $p)>{{ $p }}</option>
                                    @endforeach
                                </select>
                                @error('program') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field {{ $errors->has('year') ? 'has-error' : '' }}">
                                <label for="year">Year</label>
                                <select id="year" name="year" required>
                                    <option value="" disabled @selected(! old('year', $prefill['year']))>Year</option>
                                    @foreach (config('campus.years') as $y)
                                        <option value="{{ $y }}" @selected((string) old('year', $prefill['year']) === (string) $y)>{{ $y }}</option>
                                    @endforeach
                                </select>
                                @error('year') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field {{ $errors->has('section') ? 'has-error' : '' }}">
                                <label for="section">Section</label>
                                <select id="section" name="section" required>
                                    <option value="" disabled @selected(! old('section', $prefill['section']))>Section</option>
                                    @foreach (config('campus.sections') as $sec)
                                        <option value="{{ $sec }}" @selected((string) old('section', $prefill['section']) === (string) $sec)>{{ $sec }}</option>
                                    @endforeach
                                </select>
                                @error('section') <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-inline">Save</button>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2>Certificate of Registration</h2>
                        <span class="tag {{ $user->cor_verified_at ? 'tag-joined' : '' }}">{{ $user->cor_verified_at ? 'Verified' : 'Waiting for the admin' }}</span>
                    </div>
                    @if ($user->cor_path)
                        <p style="margin-top:0"><a href="{{ route('profile.cor') }}" target="_blank" rel="noopener">View my COR</a></p>
                    @else
                        <p class="muted-note" style="margin-top:0">No COR on file.</p>
                    @endif
                    @unless ($user->cor_verified_at)
                        <p class="muted-note">You can join events once the admin verifies your COR.</p>
                    @endunless

                    <form method="POST" action="{{ route('profile.cor.update') }}" enctype="multipart/form-data" class="profile-form" novalidate>
                        @csrf
                        <div class="field {{ $errors->has('cor') ? 'has-error' : '' }}">
                            <label for="cor">Upload a new COR</label>
                            <input type="file" id="cor" name="cor" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                            @error('cor')
                                <div class="error">{{ $message }}</div>
                            @else
                                <div class="hint">PDF, JPG or PNG, up to 5 MB. A new file has to be verified again.</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-quiet btn-sm">Upload</button>
                    </form>
                </section>
            @endunless
        </div>

        <section class="panel">
            <div class="panel-head"><h2>Change password</h2></div>
            <form method="POST" action="{{ route('profile.password') }}" class="profile-form" novalidate>
                @csrf @method('PUT')

                <div class="field {{ $errors->has('current_password') ? 'has-error' : '' }}">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    @error('current_password') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                </div>

                @include('partials.password-strength', ['for' => 'password'])

                <div class="field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                </div>

                <label class="check">
                    <input type="checkbox" data-show-password="current_password,password,password_confirmation"> Show password
                </label>

                <button type="submit" class="btn">Change password</button>
            </form>
        </section>
    </div>

    <script src="{{ asset('js/password.js') }}"></script>
@endsection
