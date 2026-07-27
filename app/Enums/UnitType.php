<?php

namespace App\Enums;

enum UnitType: string
{
    case Piece = 'piece';
    case Weight = 'weight';
    case Volume = 'volume';
    case Length = 'length';
    case Package = 'package';
    case Service = 'service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'Pieza',
            self::Weight => 'Peso',
            self::Volume => 'Volumen',
            self::Length => 'Longitud',
            self::Package => 'Paquete',
            self::Service => 'Servicio',
            self::Other => 'Otro',
        };
    }
}
