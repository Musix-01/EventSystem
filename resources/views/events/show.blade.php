@extends('layouts.app')
@section('title', $event->title)

@section('content')
    <a class="back" href="{{ route('events.index') }}">Back to events</a>

    <div class="detail">
        <section>
            <span class="org-chip">{{ $event->organization->value }}</span>
            @if ($event->status->value !== 'approved')
                <span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span>
            @endif
            <h1>{{ $event->title }}</h1>

            <dl class="facts">
                <div><dt>Date</dt><dd>{{ $event->starts_at->format('l, F j, Y') }}</dd></div>
                <div><dt>Time</dt><dd>{{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}</dd></div>
                <div><dt>Venue</dt><dd>{{ $event->venue }}</dd></div>
                <div><dt>Who can join</dt><dd>{{ $event->visibility->label() }}</dd></div>
                @if ($event->capacity)
                    <div><dt>Seats</dt><dd>{{ $event->registrations_count }} of {{ $event->capacity }} taken</dd></div>
                @endif
            </dl>

            @if ($event->description)
                <p class="prose">{{ $event->description }}</p>
            @endif
        </section>

        <aside>
            @if ($registration)
                <div class="pass">
                    <div class="pass-top">{!! $registration->qrSvg(220) !!}</div>
                    <div class="pass-cut"></div>
                    <small>Your check-in code</small>
                    <div class="pass-code">{{ $registration->spacedCode() }}</div>
                    @if ($registration->hasAttended())
                        <div class="pass-state ok">Checked in at {{ $registration->attended_at->format('g:i A') }}</div>
                    @else
                        <small>Show this to the organizer at the entrance.</small>
                    @endif
                </div>

                @unless ($registration->hasAttended() || $event->starts_at->isPast())
                    <div class="side-actions">
                        <form method="POST" action="{{ route('events.leave', $event) }}"
                              onsubmit="return confirm('Leave this event? Your code will stop working.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-quiet">Leave this event</button>
                        </form>
                    </div>
                @endunless
            @elseif ($blockedReason)
                <div class="side-note">{{ $blockedReason }}</div>
            @else
                <form method="POST" action="{{ route('events.join', $event) }}">
                    @csrf
                    <button type="submit" class="btn">Join this event</button>
                </form>
                <p class="hint" style="margin-top:.6rem;color:var(--muted)">You will get a QR code and a 6-digit code to show at the entrance.</p>
            @endif
        </aside>
    </div>
@endsection
