<?php

namespace App\Services\Sales;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use App\Support\Decimal;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SalePaymentValidatorService
{
    /**
     * @param  list<array{payment_method_id: int, amount: numeric-string}>  $payments
     * @param  numeric-string  $total
     * @param  Collection<int, PaymentMethod>  $paymentMethods  active payment methods for the company
     * @return array{amount_received: numeric-string, change_amount: numeric-string}
     */
    public function validate(array $payments, string $total, Collection $paymentMethods): array
    {
        if ($payments === []) {
            // A promotion/coupon can legitimately bring the total to zero —
            // nothing to collect, so no payment method is required.
            if (bccomp($total, '0', 4) <= 0) {
                return ['amount_received' => '0.0000', 'change_amount' => '0.0000'];
            }

            throw new InvalidArgumentException('Debes registrar al menos un método de pago.');
        }

        $sumCash = '0.0000';
        $sumNonCash = '0.0000';

        foreach ($payments as $payment) {
            $amount = Decimal::of((string) $payment['amount']);

            if (bccomp($amount, '0', 4) <= 0) {
                throw new InvalidArgumentException('El monto de cada pago debe ser mayor a cero.');
            }

            /** @var PaymentMethod|null $method */
            $method = $paymentMethods->firstWhere('id', $payment['payment_method_id']);

            if ($method === null) {
                throw new InvalidArgumentException('Uno de los métodos de pago seleccionados no es válido.');
            }

            if ($method->type === PaymentMethodType::Cash) {
                $sumCash = bcadd($sumCash, $amount, 4);
            } else {
                $sumNonCash = bcadd($sumNonCash, $amount, 4);
            }
        }

        if (bccomp($sumNonCash, $total, 4) > 0) {
            throw new InvalidArgumentException('Los métodos de pago distintos de efectivo no pueden exceder el total de la venta.');
        }

        $remainingForCash = bcsub($total, $sumNonCash, 4);

        if (bccomp($sumCash, $remainingForCash, 4) < 0) {
            throw new InvalidArgumentException('El pago no cubre el total de la venta.');
        }

        return [
            'amount_received' => bcadd($sumCash, $sumNonCash, 4),
            'change_amount' => bcsub($sumCash, $remainingForCash, 4),
        ];
    }
}
