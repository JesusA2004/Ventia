<?php

namespace App\Enums;

enum ProductType: string
{
    case Physical = 'physical';
    case Service = 'service';
    case Composite = 'composite';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Físico',
            self::Service => 'Servicio',
            self::Composite => 'Compuesto',
        };
    }

    /** Services never carry inventory. */
    public function tracksInventory(): bool
    {
        return $this !== self::Service;
    }
}
