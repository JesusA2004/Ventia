<?php

namespace App\Services\Cash;

use App\Models\CashMovement;
use App\Models\CashSession;

/**
 * The backend's answer to "how much cash should be in the drawer right now",
 * derived purely from cash_movements (the opening float plus every inflow
 * minus every outflow recorded during the session). Never trust a
 * frontend-supplied expected amount.
 */
class CalculateExpectedCashService
{
    /**
     * @return numeric-string
     */
    public function calculate(CashSession $session): string
    {
        return CashMovement::query()
            ->where('cash_session_id', $session->id)
            ->get(['type', 'amount'])
            ->reduce(function (string $expected, CashMovement $movement) {
                $signed = $movement->type->isInflow() ? $movement->amount : bcmul($movement->amount, '-1', 4);

                return bcadd($expected, $signed, 4);
            }, '0.0000');
    }
}
