<?php

namespace App\Enums;

enum EventStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Declined  = 'declined';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
