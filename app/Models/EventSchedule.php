<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSchedule extends Model
{
    protected $fillable = ['date', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function setDateAttribute(Carbon|string $value): void
    {
        $this->attributes['date'] = Carbon::parse($value)->toDateString();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
