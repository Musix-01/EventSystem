<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Letter;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            Role::Admin   => $this->admin(),
            Role::Faculty => $this->faculty(),
            Role::Officer => $this->officer($user),
            Role::Student => $this->student($user),
        };
    }

    private function student(User $user): View
    {
        $upcoming  = $this->upcomingRegistrations($user);
        $joinedIds = $user->registrations()->pluck('event_id')->all();

        // A few open events the student can still join.
        $suggested = Event::query()
            ->where('status', EventStatus::Approved->value)
            ->where('ends_at', '>=', now())
            ->visibleTo($user)
            ->whereNotIn('id', $joinedIds)
            ->withCount('registrations')
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->filter(fn (Event $e) => $e->joinBlockedReason($user) === null)
            ->take(3)
            ->values();

        $stats = [
            'upcoming' => $upcoming->count(),
            'joined'   => $user->registrations()->count(),
            'attended' => $user->registrations()->whereNotNull('attended_at')->count(),
        ];

        return view('dashboard.student', compact('user', 'upcoming', 'suggested', 'stats'));
    }

    private function officer(User $user): View
    {
        $org = $user->organization->value;

        $stats = [
            'pending'    => Event::where('organization', $org)->where('status', EventStatus::Pending->value)->count(),
            'upcoming'   => Event::where('organization', $org)->where('status', EventStatus::Approved->value)
                                ->where('ends_at', '>=', now())->count(),
            'registered' => Registration::whereHas('event', fn ($q) => $q
                                ->where('organization', $org)
                                ->where('status', EventStatus::Approved->value)
                                ->where('ends_at', '>=', now()))->count(),
            'declined'   => Event::where('organization', $org)->where('status', EventStatus::Declined->value)
                                ->where('reviewed_at', '>=', now()->subDays(30))->count(),
        ];

        $orgEvents = Event::query()
            ->where('organization', $org)
            ->where('status', EventStatus::Approved->value)
            ->where('ends_at', '>=', now())
            ->withCount(['registrations', 'registrations as attended_count' => fn ($q) => $q->whereNotNull('attended_at')])
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $declined = Event::query()
            ->where('organization', $org)
            ->where('status', EventStatus::Declined->value)
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->latest('reviewed_at')
            ->limit(3)
            ->get();

        $approved = Event::query()
            ->where('organization', $org)
            ->where('status', EventStatus::Approved->value)
            ->where('ends_at', '>=', now())
            ->where('reviewed_at', '>=', now()->subDays(7))
            ->latest('reviewed_at')
            ->limit(3)
            ->get();

        $upcoming = $this->upcomingRegistrations($user);

        return view('dashboard.officer', compact('user', 'stats', 'orgEvents', 'declined', 'approved', 'upcoming'));
    }

    private function admin(): View
    {
        $stats = [
            'pending'  => Event::where('status', EventStatus::Pending->value)->count(),
            'today'    => Event::where('status', EventStatus::Approved->value)->whereDate('starts_at', today())->count(),
            'upcoming' => Event::where('status', EventStatus::Approved->value)->where('ends_at', '>=', now())->count(),
            'letters'  => Letter::whereNull('read_at')->count(),
            'students' => User::where('role', Role::Student->value)->count(),
            'officers' => User::where('role', Role::Officer->value)->count(),
        ];

        $pending = Event::query()
            ->where('status', EventStatus::Pending->value)
            ->with('requester')
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        // Events happening from now until the end of next week, with live attendance numbers.
        $schedule = Event::query()
            ->where('status', EventStatus::Approved->value)
            ->where('ends_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(7)->endOfDay())
            ->withCount(['registrations', 'registrations as attended_count' => fn ($q) => $q->whereNotNull('attended_at')])
            ->orderBy('starts_at')
            ->get();

        $newAccounts = User::query()
            ->where('role', Role::Student->value)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.admin', compact('stats', 'pending', 'schedule', 'newAccounts'));
    }

    private function faculty(): View
    {
        $base = fn () => Event::query()
            ->where('status', EventStatus::Approved->value)
            ->withCount(['registrations', 'registrations as attended_count' => fn ($q) => $q->whereNotNull('attended_at')]);

        // Coming up in the next 30 days
        $upcoming = $base()
            ->where('ends_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(30)->endOfDay())
            ->orderBy('starts_at')
            ->get();

        // Finished in the last 14 days, so attendance can still be printed
        $recent = $base()
            ->where('ends_at', '<', now())
            ->where('ends_at', '>=', now()->subDays(14))
            ->orderByDesc('ends_at')
            ->get();

        $stats = [
            'today'    => Event::where('status', EventStatus::Approved->value)->whereDate('starts_at', today())->count(),
            'upcoming' => $upcoming->count(),
            'recent'   => $recent->count(),
        ];

        return view('dashboard.faculty', compact('stats', 'upcoming', 'recent'));
    }

    /** The user's joined events that have not ended yet, soonest first. */
    private function upcomingRegistrations(User $user): Collection
    {
        return $user->registrations()
            ->whereHas('event', fn ($q) => $q
                ->where('status', EventStatus::Approved->value)
                ->where('ends_at', '>=', now()))
            ->with('event')
            ->get()
            ->sortBy(fn (Registration $r) => $r->event->starts_at)
            ->values();
    }
}
