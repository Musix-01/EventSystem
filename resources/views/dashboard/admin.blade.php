@extends('layouts.app')
@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@section('content')
<div class="dash">
    <div class="dash-main">
        <div class="stat-row">
            @include('partials.stat-card', ['icon' => 'check', 'tone' => 'gold', 'title' => 'Requests to review', 'value' => $stats['pending'], 'href' => route('admin.events.index')])
            @include('partials.stat-card', ['icon' => 'mail', 'tone' => 'maroon', 'title' => 'New letters', 'value' => $stats['letters'], 'href' => route('admin.letters.index')])
            @include('partials.stat-card', ['icon' => 'calendar', 'tone' => 'green', 'title' => 'Events today', 'value' => $stats['today'], 'href' => route('events.index')])
            @include('partials.stat-card', ['icon' => 'users', 'tone' => 'maroon', 'title' => 'Accounts', 'value' => $stats['students'] + $stats['officers'] + $stats['faculty'], 'href' => route('admin.users.index')])
        </div>

        <section class="panel">
            <div class="panel-head"><h2>Ongoing event</h2><a href="{{ route('events.index') }}">All events</a></div>
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
            <div class="panel-head"><h2>Upcoming event (next 7 days)</h2><a href="{{ route('events.index') }}">See all</a></div>
            @if ($upcoming->isEmpty())
                <p class="muted-note">No approved events in the next 7 days.</p>
            @else
                <div class="ecards">
                    @foreach ($upcoming as $event)
                        @include('partials.event-card', [
                            'event' => $event, 'variant' => 'upcoming', 'href' => route('attendance.show', $event),
                            'line' => $event->registrations_count . ($event->capacity ? ' / ' . $event->capacity : '') . ' registered, ' . $event->venue,
                        ])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Waiting for review</h2>
                @if ($stats['pending'] > $pending->count()) <a href="{{ route('admin.events.index') }}">See all {{ $stats['pending'] }}</a> @endif
            </div>
            @if ($pending->isEmpty())
                <p class="muted-note">No requests waiting. You are all caught up.</p>
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
                                <td>@if ($event->letter) <a href="{{ route('letters.show', $event->letter) }}">Read</a> @else <span class="sub">None</span> @endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head"><h2>Manage users</h2><a href="{{ route('admin.users.index') }}">All users</a></div>
            @if ($newAccounts->isEmpty())
                <p class="muted-note">No student accounts yet.</p>
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
                                    @if ($u->cor_path) <a href="{{ route('admin.users.cor', $u) }}" target="_blank" rel="noopener">View COR</a>
                                    @else <span class="sub">None</span> @endif
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
        </section>
    </div>

    <aside class="dash-side">
        @include('partials.activity', ['items' => $activity])
    </aside>
</div>
@endsection
