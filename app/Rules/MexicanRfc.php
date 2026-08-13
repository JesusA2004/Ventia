<?php

namespace App\Rules;

use App\Enums\CustomerType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a Mexican RFC's structure (not just its length): 3 or 4 letters
 * depending on persona física/moral, a YYMMDD date, and a 3-character
 * homoclave. The FormRequest is expected to have already uppercased and
 * trimmed the value (see prepareForValidation in Store/UpdateCustomerRequest)
 * so this rule only has to validate shape.
 */
class MexicanRfc implements ValidationRule
{
    public function __construct(private readonly ?CustomerType $customerType) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->customerType === CustomerType::Business) {
            $this->validateShape($value, 12, '^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$', 'El RFC de una empresa debe contener 12 caracteres con el formato correcto (3 letras, fecha AAMMDD y homoclave).', $fail);

            return;
        }

        if ($this->customerType === CustomerType::Individual) {
            $this->validateShape($value, 13, '^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$', 'El RFC de una persona física debe contener 13 caracteres con el formato correcto (4 letras, fecha AAMMDD y homoclave).', $fail);

            return;
        }

        // General public / no customer type selected: accept either shape,
        // since the field itself is optional in that case.
        if (! preg_match('/^([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})$/', $value)) {
            $fail('El RFC no tiene un formato válido.');
        }
    }

    private function validateShape(string $value, int $expectedLength, string $pattern, string $message, Closure $fail): void
    {
        if (mb_strlen($value) !== $expectedLength || ! preg_match("/{$pattern}/", $value)) {
            $fail($message);

            return;
        }

        // Homoclave is the last 3 chars; the 6 before it are YYMMDD.
        $month = (int) substr($value, -7, 2);
        $day = (int) substr($value, -5, 2);

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            $fail('El RFC contiene una fecha no válida.');
        }
    }
}
