<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $view = ($request->query('view') === 'mine' && ! $user->isStaff()) ? 'mine' : 'all';

        $events = Event::query()
            ->where('status', EventStatus::Approved->value)
            ->where('ends_at', '>=', now())
            ->visibleTo($user)
            ->when($view === 'mine', fn ($q) => $q->where('organization', $user->organization->value))
            ->when($q !== '', fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('venue', 'like', "%{$q}%")
                  ->orWhere('organization', 'like', "%{$q}%");
            }))
            ->withCount('registrations')
            ->orderBy('starts_at')
            ->get();

        $joinedIds = $user->registrations()->pluck('event_id')->all();

        return view('home', compact('events', 'joinedIds', 'view', 'q'));
    }
}
