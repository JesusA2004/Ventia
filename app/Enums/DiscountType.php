<?php

namespace App\Enums;

/**
 * The benefit shape shared by Promotion and Coupon. Deliberately just these
 * two for the first version — 2x1/3x2/precio especial need per-unit line
 * logic the append-only Sale/SaleItem model doesn't support yet without a
 * separate design pass, so they're documented as pending, not half-built.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Porcentaje',
            self::FixedAmount => 'Monto fijo',
        };
    }

    /**
     * @param  numeric-string  $value  percentage (0-100) or currency amount, depending on $this
     * @param  numeric-string  $base  the amount the discount applies against
     * @return numeric-string never more than $base
     */
    public function amountOff(string $value, string $base): string
    {
        $amount = $this === self::Percentage
            ? bcmul($base, bcdiv($value, '100', 6), 4)
            : $value;

        return bccomp($amount, $base, 4) > 0 ? $base : $amount;
    }
}
