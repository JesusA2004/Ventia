<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Opening = 'opening';
    case Sale = 'sale';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Expense = 'expense';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Closing = 'closing';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Fondo inicial',
            self::Sale => 'Venta en efectivo',
            self::Deposit => 'Depósito',
            self::Withdrawal => 'Retiro',
            self::Expense => 'Gasto',
            self::Refund => 'Reembolso',
            self::Adjustment => 'Ajuste',
            self::Closing => 'Cierre de caja',
        };
    }

    /**
     * Whether this type increases (true) or decreases (false) the cash
     * expected in the drawer.
     */
    public function isInflow(): bool
    {
        return match ($this) {
            self::Opening, self::Sale, self::Deposit, self::Adjustment => true,
            self::Withdrawal, self::Expense, self::Refund, self::Closing => false,
        };
    }
}
