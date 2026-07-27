<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Boundary helper: narrows an arbitrary numeric-looking string coming from
 * request input or array data into a numeric-string, which is what every
 * bcmath call in the Inventory/Pricing actions requires. Throws instead of
 * silently coercing invalid input — callers are expected to have already
 * validated the value (e.g. via a `numeric` Form Request rule).
 */
final class Decimal
{
    /**
     * @return numeric-string
     */
    public static function of(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Expected a numeric string, got: {$value}");
        }

        return $value;
    }
}
