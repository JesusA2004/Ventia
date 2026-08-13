<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Mexican numbers (+52) must be exactly 10 digits (no LADA/country code
 * mixed in — that lives in the separate phone_country_code field).
 * International numbers get a looser digit-count check since national
 * numbering plans vary; we deliberately don't pull in a full phone-number
 * library for this.
 */
class PhoneNumber implements ValidationRule
{
    public function __construct(private readonly ?string $countryCode) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (! ctype_digit($value)) {
            $fail('El teléfono solo debe contener dígitos, sin espacios ni guiones.');

            return;
        }

        if ($this->countryCode === '+52') {
            if (mb_strlen($value) !== 10) {
                $fail('El teléfono de México debe contener 10 dígitos.');
            }

            return;
        }

        if (mb_strlen($value) < 6 || mb_strlen($value) > 14) {
            $fail('El teléfono debe tener entre 6 y 14 dígitos.');
        }
    }
}
