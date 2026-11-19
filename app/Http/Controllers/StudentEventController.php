<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentEventController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        $user = $request->user();

        abort_unless($event->isVisibleTo($user), 404);

        $event->load(['schedules'])->loadCount('registrations');

        $registration = $event->registrations()->where('user_id', $user->id)->with('attendances')->first();

        $last = $user->year_section
            ?: $user->registrations()->whereNotNull('year_section')->latest('id')->value('year_section');
        $prefill = ['program' => null, 'year' => null, 'section' => null];
        if ($last && preg_match('/^(.+) (\d)-(\d)$/', $last, $m)) {
            $prefill = ['program' => $m[1], 'year' => $m[2], 'section' => $m[3]];
        }

        $ownOfficer = $user->isOfficer() && $event->organization === $user->organization;
        $canPost    = $ownOfficer && $event->status === EventStatus::Approved;
        $canModerate = $ownOfficer || $user->isAdmin();
        $announcements = ($registration || $ownOfficer || $user->isStaff())
            ? $event->announcements()->with('author')->get()
            : collect();

        return view('events.show', [
            'event'              => $event,
            'registration'       => $registration,
            'blockedReason'      => $event->joinBlockedReason($user),
            'prefill'            => $prefill,
            'announcements'      => $announcements,
            'canPost'            => $canPost,
            'canModerate'        => $canModerate,
            'seesAnnouncements'  => $registration || $ownOfficer || $user->isStaff(),
            'certificateReady'   => $registration
                && $registration->attendances->isNotEmpty()
                && $event->ends_at->isPast(),
        ]);
    }
}
