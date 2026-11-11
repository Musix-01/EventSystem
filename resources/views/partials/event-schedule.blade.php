@php($scheduleDays = $scheduleDays ?? $event->schedules)
<div class="schedule-list {{ ($compact ?? false) ? 'schedule-list-compact' : '' }}">
    @foreach ($scheduleDays as $day)
        <div class="schedule-line">
            <strong>{{ $day->date->format(($compact ?? false) ? 'M j' : 'l, F j, Y') }}</strong>
            <span>{{ \Illuminate\Support\Carbon::parse($day->start_time)->format('g:i A') }} to {{ \Illuminate\Support\Carbon::parse($day->end_time)->format('g:i A') }}</span>
        </div>
    @endforeach
</div>
