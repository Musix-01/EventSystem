@extends('layouts.app')
@section('title', 'Request an event')

@section('content')
    <div class="page-head">
        <div>
            <h1>Request an event</h1>
            <p>Hosted by {{ auth()->user()->organization->value }}. The request goes to the PUP admin together with an official letter.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('officer.events.store') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="field {{ $errors->has('title') ? 'has-error' : '' }}">
            <label for="title">Event title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required autofocus>
            @error('title') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('description') ? 'has-error' : '' }}">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('venue') ? 'has-error' : '' }}">
            <label for="venue">Venue</label>
            <input type="text" id="venue" name="venue" value="{{ old('venue') }}" required>
            @error('venue') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="row-2">
            <div class="field {{ $errors->has('starts_at') ? 'has-error' : '' }}">
                <label for="starts_at">Starts</label>
                <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" required>
                @error('starts_at') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field {{ $errors->has('ends_at') ? 'has-error' : '' }}">
                <label for="ends_at">Ends</label>
                <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" required>
                @error('ends_at') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row-2">
            <div class="field {{ $errors->has('visibility') ? 'has-error' : '' }}">
                <label for="visibility">Who can join</label>
                <select id="visibility" name="visibility" required>
                    @foreach ($visibilities as $v)
                        <option value="{{ $v->value }}" @selected(old('visibility', 'org') === $v->value)>{{ $v->label() }}</option>
                    @endforeach
                </select>
                @error('visibility') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field {{ $errors->has('capacity') ? 'has-error' : '' }}">
                <label for="capacity">Student limit (optional)</label>
                <input type="number" id="capacity" name="capacity" min="1" value="{{ old('capacity') }}">
                @error('capacity') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field {{ $errors->has('purpose') ? 'has-error' : '' }}">
            <label for="purpose">Purpose of the event (goes into the request letter)</label>
            <textarea id="purpose" name="purpose" rows="5" required>{{ old('purpose') }}</textarea>
            @error('purpose') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('letter_file') ? 'has-error' : '' }}">
            <label for="letter_file">Signed letter (optional)</label>
            <input type="file" id="letter_file" name="letter_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            @error('letter_file')
                <div class="error">{{ $message }}</div>
            @else
                <div class="hint">Upload a scan if your adviser already signed one. PDF, JPG or PNG, up to 5 MB.</div>
            @enderror
        </div>

        <button type="submit" class="btn">Send request and letter</button>
    </form>
@endsection
