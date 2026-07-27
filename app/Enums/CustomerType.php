<?php

namespace App\Enums;

enum CustomerType: string
{
    case GeneralPublic = 'general_public';
    case Individual = 'individual';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::GeneralPublic => 'Público general',
            self::Individual => 'Persona física',
            self::Business => 'Empresa',
        };
    }
}
