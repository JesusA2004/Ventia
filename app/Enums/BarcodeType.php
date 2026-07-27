<?php

namespace App\Enums;

enum BarcodeType: string
{
    case Ean13 = 'EAN13';
    case Ean8 = 'EAN8';
    case Upc = 'UPC';
    case Code128 = 'CODE128';
    case Qr = 'QR';
    case Internal = 'internal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ean13 => 'EAN-13',
            self::Ean8 => 'EAN-8',
            self::Upc => 'UPC',
            self::Code128 => 'CODE128',
            self::Qr => 'QR',
            self::Internal => 'Interno',
            self::Other => 'Otro',
        };
    }

    /**
     * Digit-length rule for the formats we can actually validate.
     * Returns null for formats without a fixed length (CODE128, QR, internal, other).
     */
    public function expectedLength(): ?int
    {
        return match ($this) {
            self::Ean13 => 13,
            self::Ean8 => 8,
            self::Upc => 12,
            default => null,
        };
    }
}
