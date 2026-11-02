<?php

namespace App\Http\Controllers\Officer;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    /** All events of the officer's own organization. */
    public function index(Request $request): View
    {
        $events = Event::query()
            ->where('organization', $request->user()->organization->value)
            ->with('requester')
            ->orderByDesc('starts_at')
            ->get();

        return view('officer.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('officer.events.create', [
            'visibilities' => EventVisibility::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'venue'       => ['required', 'string', 'max:150'],
            'starts_at'   => ['required', 'date', 'after:now'],
            'ends_at'     => ['required', 'date', 'after:starts_at'],
            'visibility'  => ['required', Rule::enum(EventVisibility::class)],
            'capacity'    => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'starts_at.after' => 'The start time must be in the future.',
            'ends_at.after'   => 'The end time must be after the start time.',
        ]);

        Event::create($data + [
            'requested_by' => $request->user()->id,
            'organization' => $request->user()->organization->value, // always the officer's own org
            'status'       => EventStatus::Pending->value,
        ]);

        return redirect()->route('officer.events.index')
            ->with('status', 'Event request sent. The admin will review it.');
    }

    /** Cancel a pending or approved event of your own org. */
    public function cancel(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->organization === $request->user()->organization, 403);

        if (! in_array($event->status, [EventStatus::Pending, EventStatus::Approved], true)) {
            return back()->with('error', 'Only pending or approved events can be cancelled.');
        }

        $event->update(['status' => EventStatus::Cancelled->value]);

        return back()->with('status', 'Event cancelled.');
    }
}
