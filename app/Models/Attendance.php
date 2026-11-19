<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's check-in for one day of an event. */
class Attendance extends Model
{
    protected $fillable = ['registration_id', 'event_schedule_id', 'attended_at', 'checked_in_by'];

    protected function casts(): array
    {
        return ['attended_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class, 'event_schedule_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
