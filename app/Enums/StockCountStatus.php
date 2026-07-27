<?php

namespace App\Enums;

enum StockCountStatus: string
{
    case Draft = 'draft';
    case Counting = 'counting';
    case Completed = 'completed';
    case Applied = 'applied';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Counting => 'Contando',
            self::Completed => 'Completado',
            self::Applied => 'Aplicado',
            self::Cancelled => 'Cancelado',
        };
    }
}
