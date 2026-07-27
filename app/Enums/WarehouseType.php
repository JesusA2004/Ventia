<?php

namespace App\Enums;

enum WarehouseType: string
{
    case General = 'general';
    case SalesFloor = 'sales_floor';
    case Storage = 'storage';
    case Returns = 'returns';
    case Damaged = 'damaged';
    case Transit = 'transit';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::SalesFloor => 'Piso de venta',
            self::Storage => 'Almacenamiento',
            self::Returns => 'Devoluciones',
            self::Damaged => 'Dañados',
            self::Transit => 'Tránsito',
        };
    }
}
