<?php

namespace App\Enums;

enum EventVisibility: string
{
    case All = 'all';   // any student can join
    case Org = 'org';   // only members of the hosting organization

    public function label(): string
    {
        return match ($this) {
            self::All => 'Open to all students',
            self::Org => 'Org members only',
        };
    }
}
