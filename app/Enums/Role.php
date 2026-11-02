<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';   // default for every new account
    case Officer = 'officer';   // student org leader
    case Admin   = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Officer => 'Org officer',
            self::Admin   => 'Admin',
        };
    }
}
