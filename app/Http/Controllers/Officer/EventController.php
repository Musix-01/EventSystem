<?php

namespace App\Http\Controllers\Officer;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Letter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{

public function index(Request $request): View
{
    $view = $request->query('view', 'all');
    $q = $request->query('q');

    $query = Event::query()
        ->where('organization', $request->user()->organization->value)
        ->with(['requester', 'letter', 'schedules'])
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
        $validator = Validator::make($request->all(), [
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'venue'       => ['required', 'string', 'max:150'],
            'schedule'    => ['required', 'array', 'min:1', 'max:31'],
            'schedule.*'  => ['required', 'array:date,start_time,end_time'],
            'schedule.*.date'       => ['required', 'date_format:Y-m-d', 'distinct', 'after_or_equal:today'],
            'schedule.*.start_time' => ['required', 'date_format:H:i'],
            'schedule.*.end_time'   => ['required', 'date_format:H:i'],
            'visibility'  => ['required', Rule::enum(EventVisibility::class)],
            'capacity'    => ['nullable', 'integer', 'min:1', 'max:100000'],
            'purpose'     => ['required', 'string', 'min:20', 'max:3000'],
            'letter_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'schedule.required'                 => 'Add at least one event day.',
            'schedule.max'                      => 'An event can have at most 31 scheduled days.',
            'schedule.*.date.distinct'          => 'Each event day must have a different date.',
            'schedule.*.date.after_or_equal'    => 'Event dates must be today or later.',
            'schedule.*.start_time.required'    => 'Enter a start time for this day.',
            'schedule.*.end_time.required'      => 'Enter an end time for this day.',
            'purpose.required'   => 'Explain the purpose of the event. It goes into the request letter.',
            'purpose.min'        => 'Write at least a couple of sentences for the request letter.',
            'letter_file.mimes'  => 'The signed letter must be a PDF, JPG or PNG file.',
            'letter_file.max'    => 'The signed letter must be 5 MB or smaller.',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('schedule', []) as $index => $day) {
                if (! is_array($day)
                    || ! isset($day['date'], $day['start_time'], $day['end_time'])
                    || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $day['date'])
                    || ! preg_match('/^\d{2}:\d{2}$/', (string) $day['start_time'])
                    || ! preg_match('/^\d{2}:\d{2}$/', (string) $day['end_time'])
                    || $validator->errors()->has("schedule.{$index}.date")
                    || $validator->errors()->has("schedule.{$index}.start_time")
                    || $validator->errors()->has("schedule.{$index}.end_time")) {
                    continue;
                }

                $start = Carbon::createFromFormat('!Y-m-d H:i', "{$day['date']} {$day['start_time']}");
                $end = Carbon::createFromFormat('!Y-m-d H:i', "{$day['date']} {$day['end_time']}");

                if ($start->greaterThanOrEqualTo($end)) {
                    $validator->errors()->add("schedule.{$index}.end_time", 'The end time must be later than the start time on the same day.');
                }

                if ($start->lessThanOrEqualTo(now())) {
                    $validator->errors()->add("schedule.{$index}.start_time", 'Each event day must start in the future.');
                }
            }
        });

        $data = $validator->validate();
        $days = collect($data['schedule'])
            ->sortBy('date')
            ->values()
            ->all();
        $first = $days[0];
        $last = $days[array_key_last($days)];
        $startsAt = Carbon::createFromFormat('!Y-m-d H:i', "{$first['date']} {$first['start_time']}");
        $endsAt = Carbon::createFromFormat('!Y-m-d H:i', "{$last['date']} {$last['end_time']}");

        DB::transaction(function () use ($request, $data, $days, $startsAt, $endsAt) {
            $event = Event::create(collect($data)->except(['schedule', 'purpose', 'letter_file'])->all() + [
                'requested_by' => $request->user()->id,
                'organization' => $request->user()->organization->value, // always the officer's own org
                'starts_at'    => $startsAt,
                'ends_at'      => $endsAt,
                'status'       => EventStatus::Pending->value,
            ]);

            $event->schedules()->createMany(array_map(fn ($day) => [
                'date'       => $day['date'],
                'start_time' => $day['start_time'] . ':00',
                'end_time'   => $day['end_time'] . ':00',
            ], $days));

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
