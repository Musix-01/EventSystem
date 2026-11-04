<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Who registered, who showed up, and the printable attendance sheet.
 * Officers manage their own org's events. Admins and faculty can view and print any event.
 */
class AttendanceController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        $this->authorizeView($request, $event);

        return view('attendance.show', [
            'event'         => $event,
            'registrations' => $this->registrations($event),
            'canManage'     => $this->canManage($request, $event),
        ]);
    }

    public function print(Request $request, Event $event): View
    {
        $this->authorizeView($request, $event);

        $onlyPresent = $request->query('only') === 'present';

        $registrations = $this->registrations($event)
            ->when($onlyPresent, fn (Collection $c) => $c->filter->hasAttended()->values());

        return view('attendance.print', compact('event', 'registrations', 'onlyPresent'));
    }

    /** Check a student in by typing their 6-digit code (or pasting what a QR scanner reads). */
    public function checkIn(Request $request, Event $event): RedirectResponse
    {
        abort_unless($this->canManage($request, $event), 403);

        $result = $this->attemptCheckIn($request, $event, (string) $request->input('code', ''));

        return match ($result['status']) {
            'ok'        => back()->with('status', $result['message']),
            'duplicate' => back()->with('error', $result['message']),
            default     => back()->withErrors(['code' => $result['message']])->withInput(),
        };
    }

    /** Called by the camera scanner on the attendance page. Always answers with JSON. */
    public function scan(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->canManage($request, $event), 403);

        $result = $this->attemptCheckIn($request, $event, (string) $request->input('code', ''));
        $r      = $result['registration'];

        return response()->json([
            'status'          => $result['status'],   // ok | duplicate | error
            'message'         => $result['message'],
            'registration_id' => $r?->id,
            'attended_at'     => $r?->attended_at?->format('g:i A'),
            'present'         => $event->registrations()->whereNotNull('attended_at')->count(),
            'total'           => $event->registrations()->count(),
        ]);
    }

    /**
     * Accepts a plain 6-digit code, or the QR text "CE:{event id}:{code}".
     * Returns ['status' => ok|duplicate|error, 'message' => string, 'registration' => ?Registration].
     */
    private function attemptCheckIn(Request $request, Event $event, string $raw): array
    {
        $raw = trim($raw);

        if (preg_match('/^CE:(\d+):(\d{6})$/', $raw, $m)) {
            if ((int) $m[1] !== $event->id) {
                return ['status' => 'error', 'message' => 'That QR code is for a different event.', 'registration' => null];
            }
            $code = $m[2];
        } else {
            $code = preg_replace('/\D/', '', $raw);
        }

        if (strlen($code) !== 6) {
            return ['status' => 'error', 'message' => 'Not a valid code. Enter the 6-digit code.', 'registration' => null];
        }

        $registration = $event->registrations()->where('code', $code)->with('user')->first();

        if (! $registration) {
            return ['status' => 'error', 'message' => 'No student in this event has that code.', 'registration' => null];
        }

        if ($registration->hasAttended()) {
            return [
                'status'       => 'duplicate',
                'message'      => "{$registration->user->name} was already checked in at {$registration->attended_at->format('g:i A')}.",
                'registration' => $registration,
            ];
        }

        $registration->update([
            'attended_at'   => now(),
            'checked_in_by' => $request->user()->id,
        ]);

        return ['status' => 'ok', 'message' => "{$registration->user->name} checked in.", 'registration' => $registration];
    }

    /** Manual fallback: mark someone present (lost phone), or undo a mistake. */
    public function toggle(Request $request, Event $event, Registration $registration): RedirectResponse
    {
        abort_unless($this->canManage($request, $event), 403);
        abort_unless($registration->event_id === $event->id, 404);

        if ($registration->hasAttended()) {
            $registration->update(['attended_at' => null, 'checked_in_by' => null]);
            $message = "{$registration->user->name} marked as not present.";
        } else {
            $registration->update(['attended_at' => now(), 'checked_in_by' => $request->user()->id]);
            $message = "{$registration->user->name} marked present.";
        }

        return back()->with('status', $message);
    }

    private function registrations(Event $event): Collection
    {
        return $event->registrations()
            ->with('user')
            ->get()
            ->sortBy(fn (Registration $r) => mb_strtolower($r->user->name))
            ->values();
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
