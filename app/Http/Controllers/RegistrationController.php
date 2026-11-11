<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /** "My events": everything the user joined, with their codes. */
    public function index(Request $request): View
    {
        $registrations = $request->user()->registrations()
            ->with('event')
            ->get()
            ->sortBy(fn (Registration $r) => $r->event->starts_at)
            ->values();

        return view('registrations.index', compact('registrations'));
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();

        abort_unless($event->isVisibleTo($user), 404);

        // Students must say which program, year and section they belong to before joining.
        $data = $request->validate([
            'program' => ['required', Rule::in(config('campus.programs'))],
            'year'    => ['required', Rule::in(config('campus.years'))],
            'section' => ['required', Rule::in(config('campus.sections'))],
        ], [
            'program.required' => 'Choose your program.',
            'year.required'    => 'Choose your year level.',
            'section.required' => 'Choose your section.',
            'program.in'       => 'Choose a program from the list.',
            'year.in'          => 'Choose a year from the list.',
            'section.in'       => 'Choose a section from the list.',
        ]);
        $yearSection = "{$data['program']} {$data['year']}-{$data['section']}";   // BSIT 3-1

        // Lock the event row so two students can't take the last seat at the same time.
        $blocked = DB::transaction(function () use ($event, $user, $yearSection) {
            $locked = Event::whereKey($event->id)->lockForUpdate()->first();

            if ($reason = $locked->joinBlockedReason($user)) {
                return $reason;
            }

            Registration::create([
                'event_id'     => $locked->id,
                'user_id'      => $user->id,
                'year_section' => $yearSection,
                'code'         => $this->uniqueCode($locked),
            ]);

            return null;
        });

        if ($blocked) {
            return back()->with('error', $blocked);
        }

        return redirect()->route('events.show', $event)
            ->with('status', 'You joined this event. Show your code at the entrance.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $registration = $event->registrations()->where('user_id', $request->user()->id)->firstOrFail();

        if ($registration->hasAttended()) {
            return back()->with('error', 'You were already checked in, so you can not leave this event.');
        }
        if ($event->starts_at->isPast()) {
            return back()->with('error', 'This event already started.');
        }

        $registration->delete();

        return redirect()->route('events.show', $event)->with('status', 'You left this event.');
    }

    private function uniqueCode(Event $event): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($event->registrations()->where('code', $code)->exists());

        return $code;
    }
}
