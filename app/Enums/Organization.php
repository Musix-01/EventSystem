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

    /** Plain list of values, handy for validation: Rule::in(Organization::values()) */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
