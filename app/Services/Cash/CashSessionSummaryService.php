<?php

namespace App\Services\Cash;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethodType;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\SalePayment;

/**
 * Builds the full breakdown shown on the cash-close ("arqueo") screen:
 * cash movements broken out by type, plus non-cash payment totals collected
 * during the session (card/transfer/other), which never touch cash_movements.
 */
class CashSessionSummaryService
{
    public function __construct(private readonly CalculateExpectedCashService $expectedCash) {}

    /**
     * @return array<string, numeric-string>
     */
    public function build(CashSession $session): array
    {
        $movements = CashMovement::query()->where('cash_session_id', $session->id)->get(['type', 'amount']);

        $sumByType = fn (CashMovementType $type): string => $movements
            ->where('type', $type)
            ->reduce(fn (string $carry, CashMovement $m) => bcadd($carry, $m->amount, 4), '0.0000');

        $payments = SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->where('cash_session_id', $session->id)->where('status', '!=', 'cancelled'))
            ->with('paymentMethod:id,type')
            ->get();

        $sumByPaymentTypes = fn (array $types): string => $payments
            ->filter(fn (SalePayment $p) => in_array($p->paymentMethod->type, $types, true))
            ->reduce(fn (string $carry, SalePayment $p) => bcadd($carry, $p->amount, 4), '0.0000');

        $expectedCash = $this->expectedCash->calculate($session);
        $cardTotal = $sumByPaymentTypes([PaymentMethodType::CardDebit, PaymentMethodType::CardCredit]);
        $transferTotal = $sumByPaymentTypes([PaymentMethodType::Transfer]);
        $otherTotal = $sumByPaymentTypes([PaymentMethodType::Voucher, PaymentMethodType::CustomerCredit, PaymentMethodType::Other]);

        return [
            'opening_amount' => (string) $session->opening_amount,
            'cash_sales' => $sumByType(CashMovementType::Sale),
            'deposits' => $sumByType(CashMovementType::Deposit),
            'withdrawals' => $sumByType(CashMovementType::Withdrawal),
            'expenses' => $sumByType(CashMovementType::Expense),
            'refunds' => $sumByType(CashMovementType::Refund),
            'adjustments' => $sumByType(CashMovementType::Adjustment),
            'expected_cash' => $expectedCash,
            'card_total' => $cardTotal,
            'transfer_total' => $transferTotal,
            'other_total' => $otherTotal,
            'grand_total' => bcadd($expectedCash, bcadd($cardTotal, bcadd($transferTotal, $otherTotal, 4), 4), 4),
        ];
    }
}
