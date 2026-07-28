<?php

namespace App\Enums;

enum CashHandoverStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RecountRequested = 'recount_requested';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de revisión',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
            self::RecountRequested => 'Reconteo solicitado',
        };
    }
}
