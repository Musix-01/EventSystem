@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Hi, {{ \Illuminate\Support\Str::words($user->name, 1, '') }}</h1>
            <p>{{ $user->organization->value }} officer, {{ $user->student_number }}</p>
        </div>
        <a class="btn btn-inline" href="{{ route('officer.events.create') }}">Request an event</a>
    </div>

    <div class="stats">
        <a class="stat @if ($stats['pending'] > 0) alert @endif" href="{{ route('officer.events.index') }}">
            <strong>{{ $stats['pending'] }}</strong><span>Waiting for admin</span>
        </a>
        <div class="stat"><strong>{{ $stats['upcoming'] }}</strong><span>Approved upcoming</span></div>
        <div class="stat"><strong>{{ $stats['registered'] }}</strong><span>Students registered</span></div>
        <div class="stat @if ($stats['declined'] > 0) alert @endif">
            <strong>{{ $stats['declined'] }}</strong><span>Declined this month</span>
        </div>
    </div>

    @if ($approved->isNotEmpty())
        <div class="section-head"><h2>Recently approved</h2></div>
        @foreach ($approved as $event)
            <article class="event">
                <div class="event-top">
                    <div>
                        <h3>{{ $event->title }}</h3>
                        <p class="meta">Approved {{ $event->reviewed_at->diffForHumans() }}. {{ $event->starts_at->format('M j, g:i A') }} at {{ $event->venue }}</p>
                    </div>
                    <div><a class="btn btn-sm btn-inline" href="{{ route('attendance.show', $event) }}">See attendees</a></div>
                </div>
            </article>
        @endforeach
    @endif

    @if ($declined->isNotEmpty())
        <div class="section-head"><h2>Needs your attention</h2></div>
        @foreach ($declined as $event)
            <article class="event">
                <h3>{{ $event->title }}</h3>
                <p class="meta">Declined {{ $event->reviewed_at->format('M j') }}, was set for {{ $event->starts_at->format('M j, g:i A') }}</p>
                <div class="reason"><strong>Reason:</strong> {{ $event->decline_reason }}</div>
            </article>
        @endforeach
    @endif

    <div class="section-head">
        <h2>{{ $user->organization->value }} upcoming events</h2>
        <a href="{{ route('officer.events.index') }}">Manage events</a>
    </div>

    @if ($orgEvents->isEmpty())
        <p class="note-empty">No approved events yet. Request one and the admin will review it.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Event</th><th>When</th><th>Venue</th><th class="num">Registered</th><th class="num">Checked in</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($orgEvents as $event)
                    <tr>
                        <td><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                            @if ($event->starts_at->isPast()) <span class="tag tag-live">Happening now</span> @endif</td>
                        <td>{{ $event->starts_at->format('M j, g:i A') }}</td>
                        <td>{{ $event->venue }}</td>
                        <td class="num">{{ $event->registrations_count }}@if ($event->capacity) / {{ $event->capacity }}@endif</td>
                        <td class="num">{{ $event->attended_count }}</td>
                        <td><a href="{{ route('attendance.show', $event) }}">Attendees</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section-head"><h2>Events you joined</h2></div>
    @include('dashboard._my-events')
@endsection
