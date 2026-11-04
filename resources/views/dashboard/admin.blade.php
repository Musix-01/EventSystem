@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p>Event requests, letters, accounts, and what is happening on campus.</p>
        </div>
        <a class="btn btn-inline" href="{{ route('admin.events.index') }}">Review requests</a>
    </div>

    <div class="stats">
        <a class="stat @if ($stats['pending'] > 0) alert @endif" href="{{ route('admin.events.index') }}">
            <strong>{{ $stats['pending'] }}</strong><span>Requests to review</span>
        </a>
        <a class="stat @if ($stats['letters'] > 0) alert @endif" href="{{ route('admin.letters.index') }}">
            <strong>{{ $stats['letters'] }}</strong><span>New letters</span>
        </a>
        <div class="stat"><strong>{{ $stats['today'] }}</strong><span>Events today</span></div>
        <div class="stat"><strong>{{ $stats['upcoming'] }}</strong><span>Upcoming events</span></div>
        <a class="stat" href="{{ route('admin.users.index', ['role' => 'student']) }}">
            <strong>{{ $stats['students'] }}</strong><span>Students</span>
        </a>
        <a class="stat" href="{{ route('admin.users.index', ['role' => 'officer']) }}">
            <strong>{{ $stats['officers'] }}</strong><span>Org officers</span>
        </a>
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
                <thead><tr><th>Event</th><th>Organization</th><th>Date and place</th><th>Requested by</th><th>Letter</th></tr></thead>
                <tbody>
                @foreach ($pending as $event)
                    <tr>
                        <td><a href="{{ route('admin.events.index') }}">{{ $event->title }}</a></td>
                        <td>{{ $event->organization->value }}</td>
                        <td>{{ $event->starts_at->format('M j, g:i A') }}<span class="sub">{{ $event->venue }}</span></td>
                        <td>{{ $event->requester->name }}<span class="sub">sent {{ $event->created_at->diffForHumans() }}</span></td>
                        <td>
                            @if ($event->letter) <a href="{{ route('letters.show', $event->letter) }}">Read</a>
                            @else <span class="sub">None</span> @endif
                        </td>
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
                    <tr><th>When</th><th>Event</th><th>Venue</th><th class="num">Registered</th><th class="num">Checked in</th><th></th></tr>
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
                        <td><a href="{{ route('attendance.show', $event) }}">Attendees</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section-head">
        <h2>Manage users</h2>
        <a href="{{ route('admin.users.index') }}">All users</a>
    </div>

    @if ($newAccounts->isEmpty())
        <p class="note-empty">No student accounts yet.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Newest students</th><th>Organization</th><th>COR</th><th>Action</th></tr></thead>
                <tbody>
                @foreach ($newAccounts as $u)
                    <tr>
                        <td>{{ $u->name }}<span class="sub">{{ $u->student_number }}, joined {{ $u->created_at->diffForHumans() }}</span></td>
                        <td>{{ $u->organization?->value }}</td>
                        <td>
                            @if ($u->cor_path)
                                <a href="{{ route('admin.users.cor', $u) }}" target="_blank" rel="noopener">View COR</a>
                            @else
                                <span class="sub">None</span>
                            @endif
                        </td>
                        <td>
                            <form class="inline-form" method="POST" action="{{ route('admin.users.role', $u) }}"
                                  onsubmit="return confirm('Make {{ addslashes($u->name) }} a {{ $u->organization?->value }} officer?')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="role" value="officer">
                                <button type="submit" class="link-btn">Make officer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
