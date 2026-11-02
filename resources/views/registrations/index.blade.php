@extends('layouts.app')
@section('title', 'My events')

@section('content')
    <div class="page-head">
        <div>
            <h1>My events</h1>
            <p>Events you joined and your check-in codes.</p>
        </div>
    </div>

    @forelse ($registrations as $registration)
        @php($event = $registration->event)
        <article class="event">
            <div class="mini">
                <div class="mini-code">{{ $registration->spacedCode() }}</div>
                <div>
                    <h3 style="margin:0"><a href="{{ route('events.show', $event) }}" style="color:inherit">{{ $event->title }}</a></h3>
                    <p class="meta">
                        {{ $event->organization->value }}, {{ $event->starts_at->format('M j, Y, g:i A') }}, {{ $event->venue }}
                    </p>
                </div>
                <div>
                    @if ($registration->hasAttended())
                        <span class="tag tag-joined">Checked in</span>
                    @elseif ($event->status->value === 'cancelled')
                        <span class="status status-cancelled">Cancelled</span>
                    @elseif ($event->ends_at->isPast())
                        <span class="tag">Missed</span>
                    @else
                        <span class="tag">Registered</span>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="empty">
            You have not joined any events yet. <a href="{{ route('events.index') }}">Browse events</a>
        </div>
    @endforelse
@endsection
