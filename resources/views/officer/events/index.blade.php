@extends('layouts.app')
@section('title', "My org's events")

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ auth()->user()->organization->value }} events</h1>
            <p>Requests need admin approval before students can see them.</p>
        </div>
        <a class="btn btn-inline" href="{{ route('officer.events.create') }}">Request an event</a>
    </div>
    <div class="page-head">
        <nav class="event-tabs" aria-label="Filter approved events">
            <a href="{{ route('officer.events.index', array_filter(['view' => 'all', 'q' => $q ?? null])) }}" @if ($view === 'all' || !$view) aria-current="page" @endif>All</a>
            <a href="{{ route('officer.events.index', array_filter(['view' => 'approved', 'q' => $q ?? null])) }}" @if ($view === 'approved') aria-current="page" @endif>Approved</a>
            <a href="{{ route('officer.events.index', array_filter(['view' => 'pending', 'q' => $q ?? null])) }}" @if ($view === 'pending') aria-current="page" @endif>Pending</a>
            <a href="{{ route('officer.events.index', array_filter(['view' => 'declined', 'q' => $q ?? null])) }}" @if ($view === 'declined') aria-current="page" @endif>Declined</a>
        </nav>
    </div>
    @forelse ($events as $event)
        <article class="event">
            <div class="event-top">
                <div>
                    <h3>{{ $event->title }}</h3>
                    <p class="meta">
                        {{ $event->starts_at->format('M j, Y, g:i A') }} to {{ $event->ends_at->format('g:i A') }}
                        at {{ $event->venue }}
                    </p>
                    <p class="meta">
                        {{ $event->visibility->label() }}
                        @if ($event->capacity), limit {{ $event->capacity }} students @endif
                        , requested by {{ $event->requester->name }}
                    </p>
                </div>
                <div><span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span></div>
            </div>

            @if ($event->description) <p class="desc">{{ $event->description }}</p> @endif

            @if ($event->status->value === 'declined' && $event->decline_reason)
                <div class="reason"><strong>Why it was declined:</strong> {{ $event->decline_reason }}</div>
            @endif

            <div class="event-actions">
                @if (in_array($event->status->value, ['approved', 'completed'], true))
                    <a class="btn btn-sm btn-inline" href="{{ route('attendance.show', $event) }}">
                        Attendees ({{ $event->registrations_count }})
                    </a>
                @endif
                @if ($event->letter)
                    <a class="btn btn-sm btn-quiet" href="{{ route('letters.show', $event->letter) }}">View letter</a>
                @endif
            </div>

            @if (in_array($event->status->value, ['pending', 'approved'], true))
                <div class="event-actions">
                    <form method="POST" action="{{ route('officer.events.cancel', $event) }}"
                          onsubmit="return confirm('Cancel this event?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-danger">Cancel event</button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <div class="empty">No events yet. Request your first one to get started.</div>
    @endforelse
@endsection
