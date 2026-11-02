@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p>Event requests and what is happening on campus.</p>
        </div>
        <a class="btn btn-inline" href="{{ route('admin.events.index') }}">Review requests</a>
    </div>

    <div class="stats">
        <a class="stat @if ($stats['pending'] > 0) alert @endif" href="{{ route('admin.events.index') }}">
            <strong>{{ $stats['pending'] }}</strong><span>Requests to review</span>
        </a>
        <div class="stat"><strong>{{ $stats['today'] }}</strong><span>Events today</span></div>
        <div class="stat"><strong>{{ $stats['upcoming'] }}</strong><span>Upcoming events</span></div>
        <div class="stat"><strong>{{ $stats['students'] }}</strong><span>Students</span></div>
        <div class="stat"><strong>{{ $stats['officers'] }}</strong><span>Org officers</span></div>
    </div>

    <div class="section-head">
        <h2>Waiting for review</h2>
        @if ($stats['pending'] > $pending->count())
            <a href="{{ route('admin.events.index') }}">See all {{ $stats['pending'] }}</a>
        @endif
    </div>

    @if ($pending->isEmpty())
        <p class="note-empty">No requests waiting. You are all caught up.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Event</th><th>Organization</th><th>Date and place</th><th>Requested by</th></tr></thead>
                <tbody>
                @foreach ($pending as $event)
                    <tr>
                        <td><a href="{{ route('admin.events.index') }}">{{ $event->title }}</a></td>
                        <td>{{ $event->organization->value }}</td>
                        <td>{{ $event->starts_at->format('M j, g:i A') }}<span class="sub">{{ $event->venue }}</span></td>
                        <td>{{ $event->requester->name }}<span class="sub">sent {{ $event->created_at->diffForHumans() }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section-head"><h2>Next 7 days</h2></div>

    @if ($schedule->isEmpty())
        <p class="note-empty">No approved events in the next 7 days.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>When</th><th>Event</th><th>Venue</th><th class="num">Registered</th><th class="num">Checked in</th></tr>
                </thead>
                <tbody>
                @foreach ($schedule as $event)
                    <tr>
                        <td>{{ $event->starts_at->format('D, M j') }}
                            <span class="sub">{{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}</span></td>
                        <td>{{ $event->title }}
                            <span class="sub">{{ $event->organization->value }}</span>
                            @if ($event->starts_at->isPast()) <span class="tag tag-live">Happening now</span> @endif</td>
                        <td>{{ $event->venue }}</td>
                        <td class="num">{{ $event->registrations_count }}@if ($event->capacity) / {{ $event->capacity }}@endif</td>
                        <td class="num">{{ $event->attended_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
