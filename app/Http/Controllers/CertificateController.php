<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class CertificateController extends Controller
{
    public function mine(Request $request, Event $event): View|RedirectResponse
    {
        $registration = $event->registrations()->where('user_id', $request->user()->id)->first();

        if (! $registration) {
            return redirect()->route('events.show', $event)->with('error', 'You did not join this event.');
        }

        return $this->render($event, $registration, route('events.show', $event));
    }

    public function forStudent(Request $request, Event $event, Registration $registration): View|RedirectResponse
    {
        abort_unless($registration->event_id === $event->id, 404);

        $user = $request->user();
        abort_unless($user->isStaff() || ($user->isOfficer() && $event->organization === $user->organization), 403);

        return $this->render($event, $registration, route('attendance.show', $event));
    }

    private function render(Event $event, Registration $registration, string $backTo): View|RedirectResponse
    {
        $event->load(['schedules', 'requester']);
        $registration->load(['user', 'attendances']);

        if (! in_array($event->status, [EventStatus::Approved, EventStatus::Completed], true)) {
            return redirect()->to($backTo)->with('error', 'There is no certificate for this event.');
        }

        if (! $event->ends_at->isPast()) {
            return redirect()->to($backTo)->with('error', 'The certificate will be ready after the event ends.');
        }

        if ($registration->attendances->isEmpty()) {
            return redirect()->to($backTo)->with('error', 'No attendance was recorded, so there is no certificate.');
        }

        $daysAttended = $registration->attendances->pluck('event_schedule_id')->unique()->count();

        return view('certificates.show', [
            'event'        => $event,
            'registration' => $registration,
            'student'      => $registration->user,
            'daysAttended' => $daysAttended,
            'totalDays'    => max(1, $event->schedules->count()),
            'backTo'       => $backTo,
            'number'       => sprintf('CE-%04d-%05d', $event->id, $registration->id),
        ]);
    }
}
