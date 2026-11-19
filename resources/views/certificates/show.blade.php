@extends('layouts.app')
@section('title', 'Certificate of attendance')

@section('content')
    @php
        $firstDay = $event->schedules->first()?->date ?? $event->starts_at;
        $lastDay  = $event->schedules->last()?->date ?? $event->ends_at;
        $when = $firstDay->isSameDay($lastDay)
            ? $firstDay->format('F j, Y')
            : ($firstDay->year === $lastDay->year
                ? $firstDay->format('F j') . ' to ' . $lastDay->format('F j, Y')
                : $firstDay->format('F j, Y') . ' to ' . $lastDay->format('F j, Y'));
        $host = $event->organization === \App\Enums\Organization::SC ? 'Student Council' : $event->organization->value;
    @endphp

    <div class="no-print page-head">
        <div>
            <h1>Certificate of attendance</h1>
            <p>Press Print, then choose "Save as PDF" as the destination to keep a copy.</p>
        </div>
        <div class="cell-actions">
            <a href="{{ $backTo }}">Back</a>
            <button type="button" class="btn btn-inline" onclick="window.print()">Print or save as PDF</button>
        </div>
    </div>

    <article class="cert">
        <div class="cert-frame">
            <p class="cert-country">Republic of the Philippines</p>
            <h2 class="cert-school">Polytechnic University of the Philippines</h2>

            <h1 class="cert-title">Certificate of Attendance</h1>

            <p class="cert-line">This is to certify that</p>
            <p class="cert-name">{{ $student->name }}</p>
            <p class="cert-meta">
                @if ($registration->year_section) {{ $registration->year_section }}, @endif
                Student No. {{ $student->student_number }}
            </p>

            <p class="cert-line">attended</p>
            <p class="cert-event">{{ $event->title }}</p>
            <p class="cert-line">
                hosted by the {{ $host }}, held on {{ $when }} at {{ $event->venue }}.
                @if ($totalDays > 1) Present on {{ $daysAttended }} of {{ $totalDays }} days. @endif
            </p>

            <p class="cert-line">Given this {{ now()->format('F j, Y') }}.</p>

            <div class="cert-sign">
                <div>
                    <span class="cert-sign-line"></span>
                    <strong>{{ $event->requester?->name ?? $host }}</strong>
                    <small>Event organizer, {{ $host }}</small>
                </div>
                <div>
                    <span class="cert-sign-line"></span>
                    <strong>PUP Campus Office</strong>
                    <small>Polytechnic University of the Philippines</small>
                </div>
            </div>

            <p class="cert-no">Certificate no. {{ $number }}</p>
        </div>
    </article>
@endsection
