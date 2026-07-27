<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case CardDebit = 'card_debit';
    case CardCredit = 'card_credit';
    case Transfer = 'transfer';
    case Voucher = 'voucher';
    case CustomerCredit = 'customer_credit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::CardDebit => 'Tarjeta de débito',
            self::CardCredit => 'Tarjeta de crédito',
            self::Transfer => 'Transferencia',
            self::Voucher => 'Vale',
            self::CustomerCredit => 'Crédito del cliente',
            self::Other => 'Otro',
        };
    }
}
