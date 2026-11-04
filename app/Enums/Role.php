<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';   // default for every new account
    case Officer = 'officer';   // student org leader
    case Faculty = 'faculty';   // professors: view events and print attendance, sign in with email
    case Admin   = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Officer => 'Org officer',
            self::Faculty => 'Faculty',
            self::Admin   => 'Admin',
        };
    }
}
