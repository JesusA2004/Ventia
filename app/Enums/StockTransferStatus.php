<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Received = 'received';
    case PartiallyReceived = 'partially_received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Pending => 'Pendiente de aprobación',
            self::Approved => 'Aprobada',
            self::InTransit => 'En tránsito',
            self::Received => 'Recibida',
            self::PartiallyReceived => 'Recibida parcialmente',
            self::Cancelled => 'Cancelada',
        };
    }

    /** States from which a transfer may still be cancelled safely. */
    public function isCancellable(): bool
    {
        return in_array($this, [self::Draft, self::Pending, self::Approved], true);
    }
}
