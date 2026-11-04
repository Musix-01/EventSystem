@extends('layouts.app')
@section('title', 'Request letter')

@section('content')
    @php
        $event  = $letter->event;
        $sender = $letter->sender;
    @endphp
    <div class="no-print page-head">
        <div>
            <h1>Request letter</h1>
            <p>Sent {{ $letter->created_at->format('M j, Y, g:i A') }} by {{ $sender->name }}</p>
        </div>
        <div class="cell-actions">
            @if ($letter->attachment_path)
                <a href="{{ route('letters.attachment', $letter) }}" target="_blank" rel="noopener">Signed copy</a>
            @endif
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.events.index') }}">Review request</a>
                <a href="{{ route('admin.letters.index') }}">All letters</a>
            @else
                <a href="{{ route('officer.events.index') }}">Back to my events</a>
            @endif
            <button type="button" class="btn btn-inline" onclick="window.print()">Print letter</button>
        </div>
    </div>

    <article class="paper">
        <header class="paper-head">
            <span>Republic of the Philippines</span>
            <strong>Polytechnic University of the Philippines</strong>
            <span>{{ $event->organization->value }} Student Organization</span>
        </header>

        <p>{{ $letter->created_at->format('F j, Y') }}</p>

        <p>
            The Campus Office<br>
            Polytechnic University of the Philippines
        </p>

        <p><strong>Subject: Request for approval of “{{ $event->title }}”</strong></p>

        <p>Dear Sir/Madam:</p>

        <p class="indent">Greetings!</p>

        <p class="indent">
            The {{ $event->organization->value }} respectfully requests your approval to hold the activity
            “{{ $event->title }}” with the following details:
        </p>

        <dl class="details">
            <div><dt>Date</dt><dd>{{ $event->starts_at->format('l, F j, Y') }}</dd></div>
            <div><dt>Time</dt><dd>{{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}</dd></div>
            <div><dt>Venue</dt><dd>{{ $event->venue }}</dd></div>
            <div><dt>Participants</dt><dd>
                {{ $event->visibility->label() }}@if ($event->capacity), up to {{ $event->capacity }} students @endif
            </dd></div>
        </dl>

        <p class="indent">The purpose of the activity is as follows:</p>
        <p class="quote">{{ $letter->purpose }}</p>

        <p class="indent">
            We hope for your favorable response. Thank you very much.
        </p>

        <div class="signature">
            <p>Respectfully yours,</p>
            <div class="line">
                <strong>{{ $sender->name }}</strong><br>
                {{ $event->organization->value }} Officer<br>
                Student No. {{ $sender->student_number }}
            </div>
        </div>
    </article>
@endsection
