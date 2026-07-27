<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Thin wrapper around RecordInventoryMovementAction for the manual
 * "Ajustes de inventario" screen (entrada manual, merma, daño, robo, uso
 * interno, inventario inicial, etc.).
 */
class AdjustStockAction
{
    public function __construct(private readonly RecordInventoryMovementAction $recorder) {}

    public function execute(
        Warehouse $warehouse,
        Product $product,
        ?ProductVariant $variant,
        ?ProductLot $lot,
        InventoryMovementType $type,
        string $quantity,
        string $reason,
        ?string $notes,
        User $user,
    ): InventoryMovement {
        return $this->recorder->execute([
            'company_id' => $warehouse->company_id,
            'branch_id' => $warehouse->branch_id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_lot_id' => $lot?->id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'unit_cost' => (string) ($variant ?? $product)->cost,
            'reason' => $reason,
            'notes' => $notes,
            'performed_by' => $user->id,
        ]);
    }
}
