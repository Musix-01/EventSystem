@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Hi, {{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</h1>
            <p>Faculty. Open an event to see who attended and print the sheet.</p>
        </div>
        <a class="btn btn-inline" href="{{ route('events.index') }}">Browse events</a>
    </div>

    <div class="stats">
        <div class="stat"><strong>{{ $stats['today'] }}</strong><span>Events today</span></div>
        <div class="stat"><strong>{{ $stats['upcoming'] }}</strong><span>Coming up (30 days)</span></div>
        <div class="stat"><strong>{{ $stats['recent'] }}</strong><span>Finished (last 14 days)</span></div>
    </div>

    <div class="section-head"><h2>Coming up</h2></div>

    @if ($upcoming->isEmpty())
        <p class="note-empty">No approved events in the next 30 days.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>When</th><th>Event</th><th>Venue</th><th class="num">Registered</th><th class="num">Checked in</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($upcoming as $event)
                    <tr>
                        <td>{{ $event->starts_at->format('D, M j') }}
                            <span class="sub">{{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}</span></td>
                        <td>{{ $event->title }}
                            <span class="sub">{{ $event->organization->value }}</span>
                            @if ($event->starts_at->isPast()) <span class="tag tag-live">Happening now</span> @endif</td>
                        <td>{{ $event->venue }}</td>
                        <td class="num">{{ $event->registrations_count }}@if ($event->capacity) / {{ $event->capacity }}@endif</td>
                        <td class="num">{{ $event->attended_count }}</td>
                        <td><a href="{{ route('attendance.show', $event) }}">Attendance</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section-head"><h2>Recently finished</h2></div>

    @if ($recent->isEmpty())
        <p class="note-empty">No events finished in the last 14 days.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Event</th><th>Venue</th><th class="num">Registered</th><th class="num">Attended</th><th></th></tr>
                </thead>
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
@endsection
