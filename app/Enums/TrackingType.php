<?php

namespace App\Enums;

enum TrackingType: string
{
    case Simple = 'simple';
    case Variants = 'variants';
    case Lot = 'lot';
    case Expiration = 'expiration';
    case LotAndExpiration = 'lot_and_expiration';
    case Serial = 'serial';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Variants => 'Variantes',
            self::Lot => 'Lote',
            self::Expiration => 'Caducidad',
            self::LotAndExpiration => 'Lote y caducidad',
            self::Serial => 'Número de serie',
            self::None => 'Sin seguimiento',
        };
    }

    public function usesLots(): bool
    {
        return in_array($this, [self::Lot, self::Expiration, self::LotAndExpiration], true);
    }

    public function requiresExpiration(): bool
    {
        return in_array($this, [self::Expiration, self::LotAndExpiration], true);
    }
}
