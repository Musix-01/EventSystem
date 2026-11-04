<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventReviewController extends Controller
{
    public function index(Request $request): View
    {
        $current = EventStatus::tryFrom($request->query('status', 'pending')) ?? EventStatus::Pending;

        $events = Event::query()
            ->where('status', $current->value)
            ->with(['requester', 'reviewer', 'letter'])
            ->orderBy('starts_at')
            ->get();

        $counts = Event::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.events.index', [
            'events'   => $events,
            'current'  => $current,
            'counts'   => $counts,
            'statuses' => EventStatus::cases(),
        ]);
    }

    public function approve(Request $request, Event $event): RedirectResponse
    {
        if (! $event->isPending()) {
            return back()->with('error', 'This request was already reviewed.');
        }

        $event->update([
            'status'         => EventStatus::Approved->value,
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'decline_reason' => null,
        ]);

        return back()->with('status', "“{$event->title}” approved.");
    }

    public function decline(Request $request, Event $event): RedirectResponse
    {
        if (! $event->isPending()) {
            return back()->with('error', 'This request was already reviewed.');
        }

        $data = $request->validate([
            'decline_reason' => ['required', 'string', 'max:500'],
        ], [
            'decline_reason.required' => 'Add a reason so the officers know what to change.',
        ]);

        $event->update([
            'status'         => EventStatus::Declined->value,
            'reviewed_by'    => $request->user()->id,
            'reviewed_at'    => now(),
            'decline_reason' => $data['decline_reason'],
        ]);

        return back()->with('status', "“{$event->title}” declined.");
    }
}
