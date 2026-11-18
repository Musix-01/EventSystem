<?php

namespace App\Enums;

enum Organization: string
{
    case SC  = 'STUDENT COUNCIL';
    case ACAP  = 'ACAP';
    case AECES = 'AECES';
    case ELITE = 'ELITE';
    case GIVE  = 'GIVE';
    case JEHRA = 'JEHRA';
    case JMAP  = 'JMAP';
    case JPIA  = 'JPIA';
    case PIIE  = 'PIIE';

    /** The academic organizations a student can pick when creating an account. Student Council is assigned by the admin. */
    public static function registrable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $org) => $org !== self::SC));
    }

    public static function registrableValues(): array
    {
        return array_column(self::registrable(), 'value');
    }

    /** Plain list of values, handy for validation: Rule::in(Organization::values()) */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
