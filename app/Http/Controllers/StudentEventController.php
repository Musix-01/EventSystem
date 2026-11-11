<?php

namespace App\Http\Controllers;

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

        $last = $user->registrations()->whereNotNull('year_section')->latest('id')->value('year_section');
        $prefill = ['program' => null, 'year' => null, 'section' => null];
        if ($last && preg_match('/^(.+) (\d)-(\d)$/', $last, $m)) {
            $prefill = ['program' => $m[1], 'year' => $m[2], 'section' => $m[3]];
        }

        return view('events.show', [
            'event'        => $event,
            'registration' => $event->registrations()->where('user_id', $user->id)->first(),
            'blockedReason' => $event->joinBlockedReason($user),
            'prefill'       => $prefill,
        ]);
    }
}
