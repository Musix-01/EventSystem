<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        $this->authorizeView($request, $event);
        $event->load('schedules');

        $day     = $this->selectedDay($request, $event);
        $all     = $this->registrations($event, $day);
        $section = $this->sectionFilter($request, $all);

        // How many students were checked in on each day, for the day tabs.
        $dayCounts = Attendance::query()
            ->whereIn('event_schedule_id', $event->schedules->pluck('id'))
            ->selectRaw('event_schedule_id, COUNT(*) as total')
            ->groupBy('event_schedule_id')
            ->pluck('total', 'event_schedule_id');

        return view('attendance.show', [
            'event'         => $event,
            'day'           => $day,
            'dayCounts'     => $dayCounts,
            'registrations' => $section ? $all->where('year_section', $section)->values() : $all,
            'sections'      => $all->groupBy(fn (Registration $r) => $r->year_section ?? 'No section'),
            'section'       => $section,
            'totalAll'      => $all->count(),
            'canManage'     => $this->canManage($request, $event),
        ]);
    }

    /** ?day=<id> prints one day. ?day=all prints every day side by side. */
    public function print(Request $request, Event $event): View
    {
        $this->authorizeView($request, $event);
        $event->load('schedules');

        $onlyPresent = $request->query('only') === 'present';
        $allDays     = $request->query('day') === 'all' && $event->schedules->count() > 1;
        $day         = $allDays ? null : $this->selectedDay($request, $event);

        $all     = $this->registrations($event, $day);
        $section = $this->sectionFilter($request, $all);

        $registrations = $all
            ->when($section, fn (Collection $c) => $c->where('year_section', $section)->values())
            ->when($onlyPresent, fn (Collection $c) => $c->filter(
                fn (Registration $r) => $allDays ? $r->attendances->isNotEmpty() : $r->day_attended_at !== null
            )->values());

        return view('attendance.print', compact('event', 'registrations', 'onlyPresent', 'section', 'day', 'allDays'));
    }

    // Check a student in by typing their 6-digit code
    public function checkIn(Request $request, Event $event): RedirectResponse
    {
        abort_unless($this->canManage($request, $event), 403);
        $event->load('schedules');

        $day    = $this->selectedDay($request, $event);
        $result = $this->attemptCheckIn($request, $event, $day, (string) $request->input('code', ''));

        return match ($result['status']) {
            'ok'        => back()->with('status', $result['message']),
            'duplicate' => back()->with('error', $result['message']),
            default     => back()->withErrors(['code' => $result['message']])->withInput(),
        };
    }

    // Called by the camera scanner on the attendance page
    public function scan(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->canManage($request, $event), 403);
        $event->load('schedules');

        $day    = $this->selectedDay($request, $event);
        $result = $this->attemptCheckIn($request, $event, $day, (string) $request->input('code', ''));
        $r      = $result['registration'];

        return response()->json([
            'status'          => $result['status'],   // ok | duplicate | error
            'message'         => $result['message'],
            'registration_id' => $r?->id,
            'attended_at'     => $result['attended_at']?->format('g:i A'),
            'present'         => Attendance::where('event_schedule_id', $day->id)->count(),
            'total'           => $event->registrations()->count(),
        ]);
    }

    // Manual fallback: mark someone present for the chosen day (lost phone), or undo a mistake
    public function toggle(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        abort_unless($this->canManage($request, $event), 403);
        abort_unless($registration->event_id === $event->id, 404);
        $event->load('schedules');

        $day = $this->selectedDay($request, $event);

        $existing = Attendance::where('registration_id', $registration->id)
            ->where('event_schedule_id', $day->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = "{$registration->user->name} marked as not present{$this->forDay($event, $day)}.";
        } else {
            Attendance::create([
                'registration_id'   => $registration->id,
                'event_schedule_id' => $day->id,
                'attended_at'       => now(),
                'checked_in_by'     => $request->user()->id,
            ]);
            $message = "{$registration->user->name} marked present{$this->forDay($event, $day)}.";
        }

        $this->syncRegistration($registration);

        return back()->with('status', $message);
    }

    // ------------------------------------------------------------------ helpers

    /** Accepts a 6-digit code, or the QR text "CE:{event id}:{code}". */
    private function attemptCheckIn(Request $request, Event $event, EventSchedule $day, string $raw): array
    {
        $raw = trim($raw);

        if (preg_match('/^CE:(\d+):(\d{6})$/', $raw, $m)) {
            if ((int) $m[1] !== $event->id) {
                return $this->result('error', 'That QR code is for a different event.');
            }
            $code = $m[2];
        } else {
            $code = preg_replace('/\D/', '', $raw);
        }

        if (strlen($code) !== 6) {
            return $this->result('error', 'Not a valid code. Enter the 6-digit code.');
        }

        $registration = $event->registrations()->where('code', $code)->with('user')->first();

        if (! $registration) {
            return $this->result('error', 'No student in this event has that code.');
        }

        // firstOrCreate: scanning the same ticket twice never makes two rows.
        $attendance = Attendance::firstOrCreate(
            ['registration_id' => $registration->id, 'event_schedule_id' => $day->id],
            ['attended_at' => now(), 'checked_in_by' => $request->user()->id]
        );

        $name = $registration->user->name . ($registration->year_section ? " ({$registration->year_section})" : '');

        if (! $attendance->wasRecentlyCreated) {
            return $this->result(
                'duplicate',
                "{$name} was already checked in at {$attendance->attended_at->format('g:i A')}{$this->forDay($event, $day)}.",
                $registration,
                $attendance->attended_at
            );
        }

        $this->syncRegistration($registration);

        return $this->result('ok', "{$name} checked in{$this->forDay($event, $day)}.", $registration, $attendance->attended_at);
    }

    private function result(string $status, string $message, ?Registration $registration = null, $attendedAt = null): array
    {
        return ['status' => $status, 'message' => $message, 'registration' => $registration, 'attended_at' => $attendedAt];
    }

    /** " for Nov 13" on multi-day events, so the officer can see which day was recorded. */
    private function forDay(Event $event, EventSchedule $day): string
    {
        return $event->schedules->count() > 1 ? ' for ' . $day->date->format('M j') : '';
    }

    /** registrations.attended_at means "checked in on at least one day". Keep it true to the attendance rows. */
    private function syncRegistration(Registration $registration): void
    {
        $first = $registration->attendances()->orderBy('attended_at')->first();

        $registration->update([
            'attended_at'   => $first?->attended_at,
            'checked_in_by' => $first?->checked_in_by,
        ]);
    }

    /** ?day=<schedule id>, else today's session, else the next one coming up, else the last one. */
    private function selectedDay(Request $request, Event $event): EventSchedule
    {
        $schedules = $event->schedules;

        abort_if($schedules->isEmpty(), 404, 'This event has no schedule.');

        $wanted = (int) $request->input('day');

        if ($wanted && ($match = $schedules->firstWhere('id', $wanted))) {
            return $match;
        }

        return $schedules->first(fn (EventSchedule $s) => $s->date->isToday())
            ?? $schedules->first(fn (EventSchedule $s) => $s->date->isFuture())
            ?? $schedules->last();
    }

    /** Everyone registered, sorted by year and section then name. When $day is set, each gets ->day_attended_at. */
    private function registrations(Event $event, ?EventSchedule $day = null): Collection
    {
        $registrations = $event->registrations()->with(['user', 'attendances'])->get();

        if ($day) {
            foreach ($registrations as $registration) {
                $registration->setAttribute(
                    'day_attended_at',
                    $registration->attendances->firstWhere('event_schedule_id', $day->id)?->attended_at
                );
            }
        }

        return $registrations
            ->sortBy(fn (Registration $r) => [
                $r->year_section ? 0 : 1,
                mb_strtolower((string) $r->year_section),
                mb_strtolower($r->user->name),
            ])
            ->values();
    }

    private function sectionFilter(Request $request, Collection $all): ?string
    {
        $wanted = $request->query('section');

        return is_string($wanted) && $all->contains('year_section', $wanted) ? $wanted : null;
    }

    private function authorizeView(Request $request, Event $event): void
    {
        $user = $request->user();

        $allowed = $user->isStaff()
            || ($user->isOfficer() && $event->organization === $user->organization);

        abort_unless($allowed, 403);
        abort_unless(in_array($event->status, [EventStatus::Approved, EventStatus::Completed], true), 404);
    }

    private function canManage(Request $request, Event $event): bool
    {
        $user = $request->user();

        return $user->isOfficer()
            && $event->organization === $user->organization
            && $event->status === EventStatus::Approved;
    }
}
