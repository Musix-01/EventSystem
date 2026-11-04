@extends('layouts.app')
@section('title', 'Events')

@section('content')
    <div class="page-head">
        <div>
            <h1>Upcoming events</h1>
            <p>
                @if (auth()->user()->isStaff()) All approved events.
                @else Open events and events from {{ auth()->user()->organization->value }}.
                @endif
            </p>
        </div>
    </div>

    @if ($q !== '')
        <p class="note-empty" style="margin-bottom:1rem">Results for “{{ $q }}”. <a href="{{ route('events.index') }}">Clear search</a></p>
    @endif

    @unless (auth()->user()->isStaff())
        <nav class="tabs" aria-label="Filter events">
            <a href="{{ route('events.index', array_filter(['q' => $q])) }}" @if ($view === 'all') aria-current="page" @endif>All events</a>
            <a href="{{ route('events.index', array_filter(['view' => 'mine', 'q' => $q])) }}" @if ($view === 'mine') aria-current="page" @endif>
                {{ auth()->user()->organization->value }} only
            </a>
        </nav>
    @endunless

    @if ($events->isEmpty())
        <div class="empty">
            @if ($view === 'mine') {{ auth()->user()->organization->value }} has no upcoming events yet.
            @else No upcoming events yet. Check back soon.
            @endif
        </div>
    @else
        <div class="grid">
            @foreach ($events as $event)
                @php($left = $event->seatsLeft())
                <a class="card" href="{{ route('events.show', $event) }}">
                    <div><span class="org-chip">{{ $event->organization->value }}</span></div>
                    <h3>{{ $event->title }}</h3>
                    <p class="when">{{ $event->starts_at->format('D, M j, g:i A') }}</p>
                    <p class="where">{{ $event->venue }}</p>
                    <div class="tags">
                        @if (in_array($event->id, $joinedIds, true)) <span class="tag tag-joined">Joined</span> @endif
                        @if ($event->visibility->value === 'org') <span class="tag">{{ $event->organization->value }} members only</span>
                        @else <span class="tag">Open to all</span> @endif
                        @if ($left === 0) <span class="tag tag-full">Full</span>
                        @elseif ($left !== null) <span class="tag">{{ $left }} seats left</span> @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
