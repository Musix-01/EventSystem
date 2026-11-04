@extends('layouts.app')
@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@section('content')
<div class="dash">
    <div class="dash-main">
        <p class="greet">Hi, {{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}. Open an event to see who attended and print the sheet.</p>

        <div class="stat-row">
            @include('partials.stat-card', ['icon' => 'calendar', 'tone' => 'maroon', 'title' => 'Events today', 'value' => $stats['today'], 'caption' => 'approved'])
            @include('partials.stat-card', ['icon' => 'ticket', 'tone' => 'gold', 'title' => 'Coming up', 'value' => $stats['upcoming'], 'caption' => 'in 30 days'])
            @include('partials.stat-card', ['icon' => 'check', 'tone' => 'green', 'title' => 'Finished', 'value' => $stats['recent'], 'caption' => 'in the last 14 days'])
        </div>

        <section class="panel">
            <div class="panel-head"><h2>Ongoing event</h2></div>
            @if ($ongoing->isEmpty())
                <p class="muted-note">No event is happening right now.</p>
            @else
                <div class="ecards">
                    @foreach ($ongoing as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'ongoing', 'href' => route('attendance.show', $event),
                            'line' => $event->attended_count . ' checked in of ' . $event->registrations_count,
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Upcoming event</h2><a href="{{ route('events.index') }}">See all</a></div>
            @if ($upcoming->isEmpty())
                <p class="muted-note">No approved events in the next 30 days.</p>
            @else
                <div class="ecards">
                    @foreach ($upcoming as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'upcoming', 'href' => route('attendance.show', $event),
                            'line' => $event->registrations_count . ' registered, ' . $event->venue,
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Recently finished</h2></div>
            @if ($recent->isEmpty())
                <p class="muted-note">No events finished in the last 14 days.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Date</th><th>Event</th><th>Venue</th><th class="num">Registered</th><th class="num">Attended</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($recent as $event)
                            <tr>
                                <td>{{ $event->starts_at->format('D, M j') }}</td>
                                <td>{{ $event->title }}<span class="sub">{{ $event->organization->value }}</span></td>
                                <td>{{ $event->venue }}</td>
                                <td class="num">{{ $event->registrations_count }}</td>
                                <td class="num">{{ $event->attended_count }}</td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="{{ route('attendance.show', $event) }}">Attendance</a>
                                        <a href="{{ route('attendance.print', $event) }}" target="_blank">Print</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <aside class="dash-side">
        @include('partials.activity', ['items' => $activity])
        @include('partials.area-chart', ['series' => $series, 'title' => 'Registrations'])
    </aside>
</div>
@endsection
