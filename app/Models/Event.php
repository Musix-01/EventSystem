<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    protected $fillable = [
        'requested_by', 'organization', 'title', 'description', 'venue',
        'starts_at', 'ends_at', 'visibility', 'capacity', 'status',
        'reviewed_by', 'reviewed_at', 'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
            'reviewed_at'  => 'datetime',
            'organization' => Organization::class,
            'visibility'   => EventVisibility::class,
            'status'       => EventStatus::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function letter(): HasOne
    {
        return $this->hasOne(Letter::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class)->orderBy('date');
    }

    public function nextSchedule(): ?EventSchedule
    {
        return $this->schedules->first(fn (EventSchedule $schedule) => Carbon::parse(
            $schedule->date->toDateString() . ' ' . $schedule->start_time
        )->isFuture());
    }

    public function scopeHappeningNow(Builder $query): Builder
    {
        return $query->whereHas('schedules', fn ($schedule) => $schedule
            ->whereDate('date', today())
            ->where('start_time', '<=', now()->format('H:i:s'))
            ->where('end_time', '>', now()->format('H:i:s')));
    }

    public function scopeHasUpcomingSchedule(Builder $query, ?Carbon $before = null): Builder
    {
        return $query->whereHas('schedules', fn ($schedule) => $schedule
            ->when($before, fn ($schedule) => $schedule->whereDate('date', '<=', $before))
            ->where(function ($dates) {
                $dates->where('date', '>', today()->toDateString())
                    ->orWhere(function ($today) {
                        $today->whereDate('date', today())
                            ->where('start_time', '>', now()->format('H:i:s'));
                    });
            }));
    }

    public function isPending(): bool
    {
        return $this->status === EventStatus::Pending;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isStaff()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('visibility', EventVisibility::All->value)
              ->orWhere('organization', $user->organization->value);
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isOfficer() && $this->organization === $user->organization) {
            return true;
        }

        if (! in_array($this->status, [EventStatus::Approved, EventStatus::Completed], true)) {
            return false;
        }

        return $this->visibility === EventVisibility::All
            || $this->organization === $user->organization;
    }

    public function seatsLeft(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        $taken = $this->registrations_count ?? $this->registrations()->count();

        return max(0, $this->capacity - $taken);
    }

    public function joinBlockedReason(User $user): ?string
    {
        if ($user->isStaff()) {
            return 'Admins and faculty monitor events but do not join them.';
        }
        if ($this->status !== EventStatus::Approved) {
            return 'This event is not open for registration.';
        }
        if ($this->ends_at->isPast()) {
            return 'This event has already ended.';
        }
        if ($this->visibility === EventVisibility::Org && $this->organization !== $user->organization) {
            return 'Only ' . $this->organization->value . ' members can join this event.';
        }
        if ($this->registrations()->where('user_id', $user->id)->exists()) {
            return 'You already joined this event.';
        }
        if ($this->seatsLeft() === 0) {
            return 'This event is full.';
        }

        return null;
    }

    public function venueConflict(): ?EventSchedule
    {
        $schedules = $this->schedules;
        $conflicts = EventSchedule::query()
            ->whereIn('date', $schedules->map(fn (EventSchedule $schedule) => $schedule->date->toDateString()))
            ->whereHas('event', fn (Builder $query) => $query
                ->where('status', EventStatus::Approved->value)
                ->where('id', '!=', $this->id)
                ->whereRaw('LOWER(venue) = ?', [mb_strtolower($this->venue)]))
            ->with('event')
            ->get();

        foreach ($schedules as $schedule) {
            $conflict = $conflicts->first(fn (EventSchedule $candidate) => $candidate->date->isSameDay($schedule->date)
                && $candidate->start_time < $schedule->end_time
                && $candidate->end_time > $schedule->start_time);

            if ($conflict !== null) {
                return $conflict;
            }
        }

        return null;
    }
}
