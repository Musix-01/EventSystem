@extends('layouts.app')
@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@section('content')
<div class="dash">
    <div class="dash-main">
        <p class="greet">Hi, {{ \Illuminate\Support\Str::words($user->name, 1, '') }}. {{ $user->organization->value }} officer, {{ $user->student_number }}.</p>

        <div class="stat-row">
            @include('partials.stat-card', ['icon' => 'check', 'tone' => 'gold', 'title' => 'Waiting for admin', 'value' => $stats['pending'], 'caption' => 'requests', 'href' => route('officer.events.index')])
            @include('partials.stat-card', ['icon' => 'calendar', 'tone' => 'maroon', 'title' => 'Approved events', 'value' => $stats['upcoming'], 'caption' => 'coming up', 'href' => route('officer.events.index')])
            @include('partials.stat-card', ['icon' => 'users', 'tone' => 'green', 'title' => 'Students registered', 'value' => $stats['registered'], 'caption' => 'in upcoming events', 'href' => route('registrations.index')])
        </div>

        <section class="panel">
            <div class="panel-head"><h2>Ongoing event</h2><a href="{{ route('officer.events.index') }}">Manage events</a></div>
            @if ($ongoing->isEmpty())
                <p class="muted-note">None of your events is happening right now.</p>
            @else
                <div class="ecards">
                    @foreach ($ongoing as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'ongoing', 'href' => route('attendance.show', $event),
                            'line' => $event->attended_count . ' checked in of ' . $event->registrations_count . '. Open to scan.',
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Upcoming event</h2><a href="{{ route('officer.events.index') }}">All {{ $user->organization->value }} events</a></div>
            @if ($upcoming->isEmpty())
                <p class="muted-note">No approved events coming up. <a href="{{ route('officer.events.create') }}">Request one</a>.</p>
            @else
                <div class="ecards">
                    @foreach ($upcoming as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'upcoming', 'href' => route('attendance.show', $event),
                            'line' => $event->registrations_count . ($event->capacity ? ' / ' . $event->capacity : '') . ' registered',
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        @if ($declined->isNotEmpty())
            <section class="panel">
                <div class="panel-head"><h2>Needs your attention</h2></div>
                @foreach ($declined as $event)
                    <article class="event">
                        <h3>{{ $event->title }}</h3>
                        <p class="meta">Declined {{ $event->reviewed_at->format('M j') }}, was set for {{ $event->starts_at->format('M j, g:i A') }}</p>
                        <div class="reason"><strong>Reason:</strong> {{ $event->decline_reason }}</div>
                    </article>
                @endforeach
            </section>
        @endif

        @if ($joined->isNotEmpty())
            <section class="panel">
                <div class="panel-head"><h2>Events you joined</h2><a href="{{ route('registrations.index') }}">My events</a></div>
                <div class="ecards">
                    @foreach ($joined as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'upcoming', 'href' => route('events.show', $event),
                            'line' => 'Your code: ' . $event->my_code,
                        ])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <aside class="dash-side">
        @include('partials.activity', ['items' => $activity])
    </aside>
</div>
@endsection
