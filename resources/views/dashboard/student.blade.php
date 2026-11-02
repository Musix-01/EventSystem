@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Hi, {{ \Illuminate\Support\Str::words($user->name, 1, '') }}</h1>
            <p>{{ $user->organization->value }} student, {{ $user->student_number }}</p>
        </div>
        <a class="btn btn-inline" href="{{ route('events.index') }}">Browse events</a>
    </div>

    <div class="stats">
        <div class="stat"><strong>{{ $stats['upcoming'] }}</strong><span>Upcoming events</span></div>
        <div class="stat"><strong>{{ $stats['joined'] }}</strong><span>Events joined</span></div>
        <div class="stat"><strong>{{ $stats['attended'] }}</strong><span>Events attended</span></div>
    </div>

    @include('dashboard._my-events')

    <div class="section-head">
        <h2>Open for you</h2>
        <a href="{{ route('events.index') }}">See all events</a>
    </div>

    @if ($suggested->isEmpty())
        <p class="note-empty">Nothing new to join right now.</p>
    @else
        <div class="grid">
            @foreach ($suggested as $event)
                @php($left = $event->seatsLeft())
                <a class="card" href="{{ route('events.show', $event) }}">
                    <div><span class="org-chip">{{ $event->organization->value }}</span></div>
                    <h3>{{ $event->title }}</h3>
                    <p class="when">{{ $event->starts_at->format('D, M j, g:i A') }}</p>
                    <p class="where">{{ $event->venue }}</p>
                    <div class="tags">
                        @if ($event->visibility->value === 'org') <span class="tag">{{ $event->organization->value }} members only</span>
                        @else <span class="tag">Open to all</span> @endif
                        @if ($left !== null) <span class="tag">{{ $left }} seats left</span> @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
