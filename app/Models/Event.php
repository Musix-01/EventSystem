<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\Organization;
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

    public function isPending(): bool
    {
        return $this->status === EventStatus::Pending;
    }

    /** Events a user is allowed to see in lists: open events plus their own org's. */
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

        // Officers can always open their own org's events, even while pending.
        if ($user->isOfficer() && $this->organization === $user->organization) {
            return true;
        }

        if (! in_array($this->status, [EventStatus::Approved, EventStatus::Completed], true)) {
            return false;
        }

        return $this->visibility === EventVisibility::All
            || $this->organization === $user->organization;
    }

    /** Seats left, or null when there is no limit. */
    public function seatsLeft(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        $taken = $this->registrations_count ?? $this->registrations()->count();

        return max(0, $this->capacity - $taken);
    }

    /** Null means the user can join. Otherwise the message explains why not. */
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

    /**
     * Another approved event at the same venue whose time overlaps this one.
     * Shown to the admin as a warning before approving.
     */
    public function venueConflict(): ?Event
    {
        return static::query()
            ->where('status', EventStatus::Approved->value)
            ->where('id', '!=', $this->id)
            ->whereRaw('LOWER(venue) = ?', [mb_strtolower($this->venue)])
            ->where('starts_at', '<', $this->ends_at)
            ->where('ends_at', '>', $this->starts_at)
            ->first();
    }
}
