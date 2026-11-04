@extends('layouts.app')
@section('title', 'Attendance sheet')

@section('content')
    <div class="no-print page-head">
        <div>
            <h1>Attendance sheet</h1>
            <p>{{ $onlyPresent ? 'Showing students who checked in.' : 'Showing everyone who registered.' }}</p>
        </div>
        <div class="cell-actions">
            <a href="{{ route('attendance.show', $event) }}">Back</a>
            @if ($onlyPresent)
                <a href="{{ route('attendance.print', $event) }}">Show all registered</a>
            @else
                <a href="{{ route('attendance.print', [$event, 'only' => 'present']) }}">Show present only</a>
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
            <div><dt>Date and time</dt><dd>{{ $event->starts_at->format('F j, Y, g:i A') }} to {{ $event->ends_at->format('g:i A') }}</dd></div>
            <div><dt>Venue</dt><dd>{{ $event->venue }}</dd></div>
            <div><dt>Total</dt><dd>{{ $registrations->count() }} {{ $onlyPresent ? 'present' : 'registered' }}</dd></div>
        </dl>

        <table class="sheet">
            <thead>
                <tr><th class="no">No.</th><th>Student number</th><th>Name</th><th>Org</th><th>Time in</th><th>Signature</th></tr>
            </thead>
            <tbody>
            @forelse ($registrations as $r)
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td>{{ $r->user->student_number }}</td>
                    <td>{{ $r->user->name }}</td>
                    <td>{{ $r->user->organization?->value }}</td>
                    <td>{{ $r->attended_at?->format('g:i A') }}</td>
                    <td class="sig"></td>
                </tr>
            @empty
                <tr><td colspan="6">No students to list.</td></tr>
            @endforelse
            </tbody>
        </table>

        <p style="margin-top:2.5rem">Printed {{ now()->format('F j, Y, g:i A') }}</p>
    </article>
@endsection
