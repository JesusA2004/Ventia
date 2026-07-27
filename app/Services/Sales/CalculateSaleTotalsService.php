<?php

namespace App\Services\Sales;

use App\Enums\TaxType;

/**
 * Pure decimal arithmetic for a sale line and for aggregating a full cart.
 * Never trusts a frontend-supplied total — every number here is recomputed
 * from quantity/unit_price/discount/tax inputs that the caller (CreateSaleAction)
 * has already resolved server-side.
 */
class CalculateSaleTotalsService
{
    /**
     * @param  numeric-string  $quantity
     * @param  numeric-string  $unitPrice
     * @param  numeric-string  $discountAmount  total discount for the whole line (not per unit)
     * @param  numeric-string  $taxRate  percentage (e.g. '16.0000') for Percentage type, fixed amount per unit for Fixed type
     * @return array{subtotal: numeric-string, tax_amount: numeric-string, total: numeric-string}
     */
    public function calculateLine(
        string $quantity,
        string $unitPrice,
        string $discountAmount,
        string $taxRate,
        TaxType $taxType,
        bool $includedInPrice,
    ): array {
        $gross = bcmul($quantity, $unitPrice, 4);
        $netBeforeTax = bcsub($gross, $discountAmount, 4);

        if (bccomp($netBeforeTax, '0', 4) < 0) {
            $netBeforeTax = '0.0000';
        }

        if ($taxType === TaxType::Exempt) {
            return ['subtotal' => $netBeforeTax, 'tax_amount' => '0.0000', 'total' => $netBeforeTax];
        }

        if ($taxType === TaxType::Fixed) {
            $taxAmount = bcmul($taxRate, $quantity, 4);

            return $includedInPrice
                ? ['subtotal' => bcsub($netBeforeTax, $taxAmount, 4), 'tax_amount' => $taxAmount, 'total' => $netBeforeTax]
                : ['subtotal' => $netBeforeTax, 'tax_amount' => $taxAmount, 'total' => bcadd($netBeforeTax, $taxAmount, 4)];
        }

        // Percentage
        if ($includedInPrice) {
            $divisor = bcadd('1', bcdiv($taxRate, '100', 6), 6);
            $subtotal = bcdiv($netBeforeTax, $divisor, 4);

            return ['subtotal' => $subtotal, 'tax_amount' => bcsub($netBeforeTax, $subtotal, 4), 'total' => $netBeforeTax];
        }

        $taxAmount = bcmul($netBeforeTax, bcdiv($taxRate, '100', 6), 4);

        return ['subtotal' => $netBeforeTax, 'tax_amount' => $taxAmount, 'total' => bcadd($netBeforeTax, $taxAmount, 4)];
    }

    /**
     * @param  list<array{subtotal: numeric-string, discount_amount: numeric-string, tax_amount: numeric-string, total: numeric-string, unit_cost: numeric-string, quantity: numeric-string}>  $lines
     * @return array{subtotal: numeric-string, discount_total: numeric-string, tax_total: numeric-string, total: numeric-string, cost_total: numeric-string, profit_total: numeric-string}
     */
    public function aggregate(array $lines): array
    {
        $subtotal = '0.0000';
        $discountTotal = '0.0000';
        $taxTotal = '0.0000';
        $total = '0.0000';
        $costTotal = '0.0000';

        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, $line['subtotal'], 4);
            $discountTotal = bcadd($discountTotal, $line['discount_amount'], 4);
            $taxTotal = bcadd($taxTotal, $line['tax_amount'], 4);
            $total = bcadd($total, $line['total'], 4);
            $costTotal = bcadd($costTotal, bcmul($line['unit_cost'], $line['quantity'], 4), 4);
        }

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'cost_total' => $costTotal,
            'profit_total' => bcsub($subtotal, $costTotal, 4),
        ];
    }
}
