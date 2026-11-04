<?php

namespace App\Http\Controllers\Officer;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Letter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    /** All events of the officer's own organization. */
public function index(Request $request): View
{
    $view = $request->query('view', 'all'); // Sets a default string state if none is selected
    $q = $request->query('q');

    $query = Event::query()
        ->where('organization', $request->user()->organization->value)
        ->with(['requester', 'letter'])
        ->withCount('registrations');

    if ($view === 'approved') {
        $query->where('status', \App\Enums\EventStatus::Approved->value);
    } elseif ($view === 'pending') {
        $query->where('status', \App\Enums\EventStatus::Pending->value);
    } elseif ($view === 'declined') {
        $query->where('status', \App\Enums\EventStatus::Declined->value);
    }

    if (!empty($q)) {
        $query->where('title', 'like', '%' . $q . '%');
    }

    $events = $query->orderByDesc('starts_at')->get();

    // Make sure 'view' and 'q' are added here:
    return view('officer.events.index', compact('events', 'view', 'q'));
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
            'purpose'     => ['required', 'string', 'min:20', 'max:3000'],
            'letter_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'starts_at.after'    => 'The start time must be in the future.',
            'ends_at.after'      => 'The end time must be after the start time.',
            'purpose.required'   => 'Explain the purpose of the event. It goes into the request letter.',
            'purpose.min'        => 'Write at least a couple of sentences for the request letter.',
            'letter_file.mimes'  => 'The signed letter must be a PDF, JPG or PNG file.',
            'letter_file.max'    => 'The signed letter must be 5 MB or smaller.',
        ]);

        DB::transaction(function () use ($request, $data) {
            $event = Event::create(collect($data)->except(['purpose', 'letter_file'])->all() + [
                'requested_by' => $request->user()->id,
                'organization' => $request->user()->organization->value, // always the officer's own org
                'status'       => EventStatus::Pending->value,
            ]);

            Letter::create([
                'event_id'        => $event->id,
                'sent_by'         => $request->user()->id,
                'purpose'         => $data['purpose'],
                'attachment_path' => $request->file('letter_file')?->store('letters', 'local'),
            ]);
        });

        return redirect()->route('officer.events.index')
            ->with('status', 'Event request and letter sent. The admin will review them.');
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
