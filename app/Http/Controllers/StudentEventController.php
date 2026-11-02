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

        $event->loadCount('registrations');

        return view('events.show', [
            'event'        => $event,
            'registration' => $event->registrations()->where('user_id', $user->id)->first(),
            'blockedReason' => $event->joinBlockedReason($user),
        ]);
    }
}
