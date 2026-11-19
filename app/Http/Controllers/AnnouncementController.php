<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Officers post announcements on their own organization's approved events. Joined students read them. */
class AnnouncementController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($this->canPost($request->user(), $event), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body'  => ['required', 'string', 'max:2000'],
        ], [
            'title.required' => 'Give the announcement a short title.',
            'body.required'  => 'Write the announcement.',
        ]);

        $event->announcements()->create($data + ['user_id' => $request->user()->id]);

        return redirect()->to(route('events.show', $event) . '#announcements')
            ->with('status', 'Announcement posted. Students who joined this event can see it.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $event = $announcement->event;
        $user  = $request->user();

        abort_unless($user->isAdmin() || ($user->isOfficer() && $event->organization === $user->organization), 403);

        $announcement->delete();

        return redirect()->to(route('events.show', $event) . '#announcements')
            ->with('status', 'Announcement removed.');
    }

    private function canPost(User $user, Event $event): bool
    {
        return $user->isOfficer()
            && $event->organization === $user->organization
            && $event->status === EventStatus::Approved;
    }
}
