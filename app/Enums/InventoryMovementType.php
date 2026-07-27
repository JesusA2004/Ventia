<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Initial = 'initial';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case SaleReturn = 'sale_return';
    case PurchaseReturn = 'purchase_return';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Theft = 'theft';
    case InternalUse = 'internal_use';
    case Cancellation = 'cancellation';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Inventario inicial',
            self::Purchase => 'Entrada por compra',
            self::Sale => 'Venta',
            self::SaleReturn => 'Devolución de venta',
            self::PurchaseReturn => 'Devolución a proveedor',
            self::AdjustmentIn => 'Ajuste positivo',
            self::AdjustmentOut => 'Ajuste negativo',
            self::TransferOut => 'Transferencia de salida',
            self::TransferIn => 'Transferencia de entrada',
            self::Damaged => 'Daño',
            self::Expired => 'Caducidad',
            self::Theft => 'Robo',
            self::InternalUse => 'Uso interno',
            self::Cancellation => 'Cancelación',
        };
    }

    public function direction(): InventoryMovementDirection
    {
        return match ($this) {
            self::Initial, self::Purchase, self::SaleReturn, self::AdjustmentIn, self::TransferIn => InventoryMovementDirection::In,
            self::Sale, self::PurchaseReturn, self::AdjustmentOut, self::TransferOut,
            self::Damaged, self::Expired, self::Theft, self::InternalUse, self::Cancellation => InventoryMovementDirection::Out,
        };
    }

    /**
     * Manual adjustment reasons offered in the "Ajustes de inventario" screen.
     *
     * @return list<self>
     */
    public static function manualAdjustmentTypes(): array
    {
        return [
            self::Initial, self::AdjustmentIn, self::AdjustmentOut,
            self::Damaged, self::Expired, self::Theft, self::InternalUse,
        ];
    }
}
