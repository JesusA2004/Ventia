<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryBalanceService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single entry point for touching inventory. inventory_movements is
 * append-only and inventory_balances is a projection — nothing else in the
 * codebase should insert into either table directly.
 */
class RecordInventoryMovementAction
{
    public function __construct(private readonly InventoryBalanceService $balances) {}

    /**
     * @param  array{
     *     company_id: int, branch_id: int, warehouse_id: int, product_id: int,
     *     product_variant_id?: int|null, product_lot_id?: int|null,
     *     movement_type: InventoryMovementType, quantity: string, unit_cost?: string,
     *     reference_type?: class-string|null, reference_id?: int|null,
     *     reason?: string|null, notes?: string|null, performed_by?: int|null, occurred_at?: \DateTimeInterface|null,
     * }  $data
     */
    public function execute(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->findOrFail($data['product_id']);
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);

            if ($product->company_id !== $data['company_id'] || $warehouse->company_id !== $data['company_id']) {
                throw new InvalidArgumentException('El producto y el almacén deben pertenecer a la misma empresa.');
            }

            if ($warehouse->branch_id !== $data['branch_id']) {
                throw new InvalidArgumentException('El almacén no pertenece a la sucursal indicada.');
            }

            $movementType = $data['movement_type'];
            $direction = $movementType->direction();
            $quantity = Decimal::of($data['quantity']);
            $unitCost = Decimal::of($data['unit_cost'] ?? '0');

            ['previous' => $previous, 'resulting' => $resulting] = $this->balances->applyMovement(
                $data['company_id'],
                $data['branch_id'],
                $data['warehouse_id'],
                $data['product_id'],
                $data['product_variant_id'] ?? null,
                $data['product_lot_id'] ?? null,
                $direction,
                $quantity,
                $unitCost,
            );

            if (bccomp($resulting, '0', 4) < 0 && ! $product->allows_negative_stock) {
                throw new InsufficientStockException($previous);
            }

            return InventoryMovement::query()->create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'product_lot_id' => $data['product_lot_id'] ?? null,
                'movement_type' => $movementType,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'previous_stock' => $previous,
                'resulting_stock' => $resulting,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'performed_by' => $data['performed_by'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);
        });
    }
}
