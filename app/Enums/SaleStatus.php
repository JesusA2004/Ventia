<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Draft = 'draft';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Suspended => 'Suspendida',
            self::Completed => 'Completada',
            self::PartiallyReturned => 'Devuelta parcialmente',
            self::Returned => 'Devuelta',
            self::Cancelled => 'Cancelada',
        };
    }
}
