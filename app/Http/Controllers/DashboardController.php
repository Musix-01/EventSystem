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

    // ------------------------------------------------------------------ student

    private function student(User $user): View
    {
        $registrations = $this->upcomingRegistrations($user);
        $joinedIds     = $user->registrations()->pluck('event_id')->all();

        // Happening right now, in events this student may join or already joined
        $ongoing = $this->approved()
            ->visibleTo($user)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->withCount('registrations')
            ->orderBy('ends_at')
            ->limit(4)
            ->get();

        // Joined events that have not started yet, each with the student's own code
        $upcoming = $registrations
            ->filter(fn (Registration $r) => $r->event->starts_at->isFuture())
            ->take(4)
            ->map(function (Registration $r) {
                $event = $r->event;
                $event->loadCount('registrations');
                $event->setAttribute('my_code', $r->spacedCode());

                return $event;
            })
            ->values();
        $this->withAvatars($upcoming);

        // Open events still available to join
        $suggested = $this->approved()
            ->where('ends_at', '>=', now())
            ->visibleTo($user)
            ->whereNotIn('id', $joinedIds)
            ->withCount('registrations')
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->filter(fn (Event $e) => $e->joinBlockedReason($user) === null)
            ->take(4)
            ->values();
        $this->withAvatars($suggested);

        $stats = [
            'upcoming' => $registrations->count(),
            'joined'   => $user->registrations()->count(),
            'attended' => $user->registrations()->whereNotNull('attended_at')->count(),
        ];

        $activity = collect();
        foreach ($user->registrations()->with('event')->latest()->limit(6)->get() as $r) {
            $activity->push($this->item($r->event->organization->value, 'You', "joined {$r->event->title}", $r->created_at));
            if ($r->attended_at) {
                $activity->push($this->item($r->event->organization->value, 'You', "were checked in at {$r->event->title}", $r->attended_at));
            }
        }
        $activity = $activity->sortByDesc('at')->take(6)->values();

        return view('dashboard.student', compact('user', 'stats', 'ongoing', 'upcoming', 'suggested', 'joinedIds', 'activity'));
    }

    // ------------------------------------------------------------------ officer

    private function officer(User $user): View
    {
        $org = $user->organization->value;

        $own = fn () => $this->approved()->where('organization', $org)->withCount($this->attendanceCounts());

        $ongoing = $own()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at')
            ->limit(4)
            ->get();

        $upcoming = $own()
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(4)
            ->get();
        $this->withAvatars($upcoming);

        $stats = [
            'pending'    => Event::where('organization', $org)->where('status', EventStatus::Pending->value)->count(),
            'upcoming'   => $this->approved()->where('organization', $org)->where('ends_at', '>=', now())->count(),
            'registered' => Registration::whereHas('event', fn ($q) => $q
                                ->where('organization', $org)
                                ->where('status', EventStatus::Approved->value)
                                ->where('ends_at', '>=', now()))->count(),
            'declined'   => Event::where('organization', $org)->where('status', EventStatus::Declined->value)
                                ->where('reviewed_at', '>=', now()->subDays(30))->count(),
        ];

        $declined = Event::query()
            ->where('organization', $org)
            ->where('status', EventStatus::Declined->value)
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->latest('reviewed_at')
            ->limit(3)
            ->get();

        // Events this officer joined as a student
        $joined = $this->upcomingRegistrations($user)
            ->take(4)
            ->map(function (Registration $r) {
                $event = $r->event;
                $event->loadCount('registrations');
                $event->setAttribute('my_code', $r->spacedCode());

                return $event;
            })
            ->values();

        // Activity in the officer's own org
        $orgRegs = fn () => Registration::query()
            ->whereHas('event', fn ($q) => $q->where('organization', $org))
            ->with(['user', 'event']);

        $activity = collect();
        foreach ($orgRegs()->latest()->limit(6)->get() as $r) {
            $activity->push($this->item($r->user->name, $r->user->name, "joined {$r->event->title}", $r->created_at));
        }
        foreach ($orgRegs()->whereNotNull('attended_at')->latest('attended_at')->limit(4)->get() as $r) {
            $activity->push($this->item($r->user->name, $r->user->name, "checked in to {$r->event->title}", $r->attended_at));
        }
        foreach (Event::where('organization', $org)->whereNotNull('reviewed_at')->latest('reviewed_at')->limit(3)->get() as $e) {
            $verb = $e->status === EventStatus::Declined ? 'declined' : 'approved';
            $activity->push($this->item('Admin', 'Admin', "{$verb} {$e->title}", $e->reviewed_at));
        }
        $activity = $activity->sortByDesc('at')->take(7)->values();

        $series = $this->registrationSeries($org);

        return view('dashboard.officer', compact('user', 'stats', 'ongoing', 'upcoming', 'declined', 'joined', 'activity', 'series'));
    }

    // -------------------------------------------------------------------- admin

    private function admin(): View
    {
        $ongoing = $this->approved()
            ->withCount($this->attendanceCounts())
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at')
            ->limit(4)
            ->get();

        $upcoming = $this->approved()
            ->withCount($this->attendanceCounts())
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addDays(7)->endOfDay())
            ->orderBy('starts_at')
            ->limit(4)
            ->get();
        $this->withAvatars($upcoming);

        $stats = [
            'pending'  => Event::where('status', EventStatus::Pending->value)->count(),
            'letters'  => Letter::whereNull('read_at')->count(),
            'today'    => $this->approved()->whereDate('starts_at', today())->count(),
            'students' => User::where('role', Role::Student->value)->count(),
            'officers' => User::where('role', Role::Officer->value)->count(),
            'faculty'  => User::where('role', Role::Faculty->value)->count(),
        ];

        $pending = Event::query()
            ->where('status', EventStatus::Pending->value)
            ->with(['requester', 'letter'])
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        $newAccounts = User::where('role', Role::Student->value)->latest()->limit(5)->get();

        $activity = collect();
        foreach (Letter::with(['event', 'sender'])->latest()->limit(3)->get() as $l) {
            $activity->push($this->item($l->sender->name, $l->sender->name, "sent a request letter for {$l->event->title}", $l->created_at));
        }
        foreach (User::where('role', Role::Student->value)->latest()->limit(3)->get() as $u) {
            $activity->push($this->item($u->name, $u->name, 'registered an account', $u->created_at));
        }
        foreach (Registration::with(['user', 'event'])->whereNotNull('attended_at')->latest('attended_at')->limit(3)->get() as $r) {
            $activity->push($this->item($r->user->name, $r->user->name, "checked in to {$r->event->title}", $r->attended_at));
        }
        $activity = $activity->sortByDesc('at')->take(7)->values();

        $series = $this->registrationSeries();

        return view('dashboard.admin', compact('stats', 'ongoing', 'upcoming', 'pending', 'newAccounts', 'activity', 'series'));
    }

    // ------------------------------------------------------------------ faculty

    private function faculty(): View
    {
        $ongoing = $this->approved()
            ->withCount($this->attendanceCounts())
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at')
            ->limit(4)
            ->get();

        // Coming up in the next 30 days
        $upcoming = $this->approved()
            ->withCount($this->attendanceCounts())
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addDays(30)->endOfDay())
            ->orderBy('starts_at')
            ->limit(4)
            ->get();
        $this->withAvatars($upcoming);

        // Finished in the last 14 days, so attendance can still be printed
        $recent = $this->approved()
            ->withCount($this->attendanceCounts())
            ->where('ends_at', '<', now())
            ->where('ends_at', '>=', now()->subDays(14))
            ->orderByDesc('ends_at')
            ->get();

        $stats = [
            'today'    => $this->approved()->whereDate('starts_at', today())->count(),
            'upcoming' => $this->approved()->where('ends_at', '>=', now())->where('starts_at', '<=', now()->addDays(30)->endOfDay())->count(),
            'recent'   => $recent->count(),
        ];

        $activity = Registration::with(['user', 'event'])
            ->whereNotNull('attended_at')
            ->latest('attended_at')
            ->limit(7)
            ->get()
            ->map(fn (Registration $r) => $this->item($r->user->name, $r->user->name, "checked in to {$r->event->title}", $r->attended_at))
            ->values();

        $series = $this->registrationSeries();

        return view('dashboard.faculty', compact('stats', 'ongoing', 'upcoming', 'recent', 'activity', 'series'));
    }

    // ------------------------------------------------------------------ helpers

    private function approved()
    {
        return Event::query()->where('status', EventStatus::Approved->value);
    }

    private function attendanceCounts(): array
    {
        return ['registrations', 'registrations as attended_count' => fn ($q) => $q->whereNotNull('attended_at')];
    }

    /** First few registered students per event, for the avatar row on cards. */
    private function withAvatars(Collection $events): Collection
    {
        foreach ($events as $event) {
            $event->setRelation(
                'avatarUsers',
                $event->registrations()->with('user')->orderBy('id')->limit(4)->get()->map->user
            );
        }

        return $events;
    }

    /** One entry for the "Recent activity" panel. */
    private function item(string $badgeSource, string $who, string $text, $at): array
    {
        $badge = collect(preg_split('/\s+/', trim($badgeSource)))
            ->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        // For org codes like "ELITE" use the first two letters instead of one.
        if (preg_match('/^[A-Z]{3,}$/', $badgeSource)) {
            $badge = substr($badgeSource, 0, 2);
        }

        return ['badge' => $badge, 'who' => $who, 'text' => $text, 'at' => $at];
    }

    /** Registrations per day for the last N days (one org, or everything when $org is null). */
    private function registrationSeries(?string $org = null, int $days = 14): Collection
    {
        $from = today()->subDays($days - 1);

        $rows = Registration::query()
            ->when($org, fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organization', $org)))
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        return collect(range(0, $days - 1))->map(function ($i) use ($from, $rows) {
            $day = $from->copy()->addDays($i);

            return ['label' => $day->format('M j'), 'value' => (int) ($rows[$day->toDateString()] ?? 0)];
        });
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
