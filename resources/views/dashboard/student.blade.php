@extends('layouts.app')
@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@section('content')
<div class="dash">
    <div class="dash-main">
        <div class="greet-card">
        <p class="greet">Welcome, {{ \Illuminate\Support\Str::words($user->name, 1, '') }}. </p>
            <p class = "user-info"> {{ $user->organization->value }} | {{ $user->student_number }}.</p>
        </div>
        <div class="stat-row">
            @include('partials.stat-card', ['icon' => 'calendar', 'tone' => 'maroon', 'title' => 'Upcoming events', 'value' => $stats['upcoming'], 'caption' => 'you joined', 'href' => route('registrations.index')])
            @include('partials.stat-card', ['icon' => 'ticket', 'tone' => 'gold', 'title' => 'Events joined', 'value' => $stats['joined'], 'caption' => 'all time', 'href' => route('registrations.index')])
            @include('partials.stat-card', ['icon' => 'check', 'tone' => 'green', 'title' => 'Events attended', 'value' => $stats['attended'], 'caption' => 'of ' . $stats['joined'] . ' joined'])
        </div>

        <section class="panel">
            <div class="panel-head"><h2>Ongoing event</h2><a href="{{ route('events.index') }}">See all</a></div>
            @if ($ongoing->isEmpty())
                <p class="muted-note">Nothing is happening right now.</p>
            @else
                <div class="ecards">
                    @foreach ($ongoing as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'ongoing', 'href' => route('events.show', $event),
                            'tag' => in_array($event->id, $joinedIds, true) ? 'Joined' : null,
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Your upcoming events</h2><a href="{{ route('registrations.index') }}">My events</a></div>
            @if ($upcoming->isEmpty())
                <p class="muted-note">You have not joined an upcoming event yet.</p>
            @else
                <div class="ecards">
                    @foreach ($upcoming as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'upcoming', 'href' => route('events.show', $event),
                            'line' => 'Your code: ' . $event->my_code,
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Open for you</h2><a href="{{ route('events.index') }}">See all events</a></div>
            @if ($suggested->isEmpty())
                <p class="muted-note">Nothing new to join right now.</p>
            @else
                <div class="ecards">
                    @foreach ($suggested as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'upcoming', 'href' => route('events.show', $event),
                            'line' => $event->seatsLeft() !== null ? $event->seatsLeft() . ' seats left' : null,
                        ])
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <aside class="dash-side">
        @include('partials.activity', ['items' => $activity])

    </aside>
</div>
@endsection
