@extends('layouts.app')
@section('title', 'Attendance sheet')

@section('content')
    @php($schedules = $event->schedules)

    <div class="no-print page-head">
        <div>
            <h1>Attendance sheet</h1>
            <p>
                @if ($allDays) All {{ $schedules->count() }} days side by side.
                @else {{ $onlyPresent ? 'Showing students who checked in.' : 'Showing everyone who registered.' }} @endif
            </p>
        </div>
        <div class="cell-actions">
            <a href="{{ route('attendance.show', array_filter([$event, 'day' => $day?->id, 'section' => $section])) }}">Back</a>
            @if ($onlyPresent)
                <a href="{{ route('attendance.print', array_filter([$event, 'day' => $allDays ? 'all' : $day->id, 'section' => $section])) }}">Show all registered</a>
            @else
                <a href="{{ route('attendance.print', array_filter([$event, 'day' => $allDays ? 'all' : $day->id, 'only' => 'present', 'section' => $section])) }}">Show present only</a>
            @endif
            <button type="button" class="btn btn-inline" onclick="window.print()">Print</button>
        </div>
    </div>

    <article class="paper">
        <header class="paper-head">
            <span>Republic of the Philippines</span>
            <strong>Polytechnic University of the Philippines</strong>
            <span>Attendance Sheet</span>
        </header>

        <dl class="details" style="margin-left:0">
            <div><dt>Event</dt><dd>{{ $event->title }}</dd></div>
            <div><dt>Organization</dt><dd>{{ $event->organization->value }}</dd></div>
            @if ($allDays || $schedules->count() === 1)
                <div><dt>Schedule</dt><dd>@include('partials.event-schedule', ['event' => $event])</dd></div>
            @else
                <div><dt>Day</dt><dd>
                    Day {{ $schedules->search(fn ($s) => $s->id === $day->id) + 1 }}, {{ $day->date->format('l, F j, Y') }},
                    {{ \Illuminate\Support\Carbon::parse($day->start_time)->format('g:i A') }} to {{ \Illuminate\Support\Carbon::parse($day->end_time)->format('g:i A') }}
                </dd></div>
            @endif
            <div><dt>Venue</dt><dd>{{ $event->venue }}</dd></div>
            @if ($section)<div><dt>Section</dt><dd>{{ $section }}</dd></div>@endif
            <div><dt>Total</dt><dd>{{ $registrations->count() }} {{ $onlyPresent ? 'present' : 'registered' }}</dd></div>
        </dl>

        @if ($allDays)
            {{-- One column per day. Shows the time the student was checked in. --}}
            <table class="sheet">
                <thead>
                    <tr>
                        <th class="no">No.</th><th>Student number</th><th>Name</th><th>Year &amp; Section</th>
                        @foreach ($schedules as $s)
                            <th>Day {{ $loop->iteration }}<br><small>{{ $s->date->format('M j') }}</small></th>
                        @endforeach
                        <th>Days</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($registrations as $r)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>{{ $r->user->student_number }}</td>
                        <td>{{ $r->user->name }}</td>
                        <td>{{ $r->year_section ?? '-' }}</td>
                        @foreach ($schedules as $s)
                            @php($att = $r->attendances->firstWhere('event_schedule_id', $s->id))
                            <td>{{ $att?->attended_at?->format('g:i A') }}</td>
                        @endforeach
                        <td>{{ $r->attendances->count() }} / {{ $schedules->count() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 5 + $schedules->count() }}">No students to list.</td></tr>
                @endforelse
                </tbody>
            </table>
        @else
            <table class="sheet">
                <thead>
                    <tr><th class="no">No.</th><th>Student number</th><th>Name</th><th>Year &amp; Section</th><th>Org</th><th>Time in</th></tr>
                </thead>
                <tbody>
                @forelse ($registrations as $r)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td>{{ $r->user->student_number }}</td>
                        <td>{{ $r->user->name }}</td>
                        <td>{{ $r->year_section ?? '-' }}</td>
                        <td>{{ $r->user->organization?->value }}</td>
                        <td>{{ $r->day_attended_at?->format('g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No students to list.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif

        <p style="margin-top:2.5rem">Printed {{ now()->format('F j, Y, g:i A') }}</p>
    </article>
@endsection
