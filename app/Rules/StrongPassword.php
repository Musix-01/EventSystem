<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The same three rules the password strength checklist shows:
 * at least 8 characters, one uppercase letter, one number.
 */
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (mb_strlen($value) < 8) {
            $fail('The password must be at least 8 characters.');
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('The password needs at least one uppercase letter.');
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('The password needs at least one number.');
        }
    }
}
