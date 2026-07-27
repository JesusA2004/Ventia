<?php

namespace App\Enums;

enum TaxType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Exempt = 'exempt';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Porcentaje',
            self::Fixed => 'Monto fijo',
            self::Exempt => 'Exento',
        };
    }
}
