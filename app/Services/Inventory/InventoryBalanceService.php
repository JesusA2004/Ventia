<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementDirection;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The only class allowed to write to inventory_balances. Every write happens
 * inside a transaction with a row lock, so two concurrent movements against
 * the same (warehouse, product, variant, lot) tuple serialize instead of
 * racing each other.
 */
class InventoryBalanceService
{
    /**
     * @param  numeric-string  $quantity
     * @param  numeric-string  $unitCost
     * @return array{previous: numeric-string, resulting: numeric-string}
     */
    public function applyMovement(
        int $companyId,
        int $branchId,
        int $warehouseId,
        int $productId,
        ?int $variantId,
        ?int $lotId,
        InventoryMovementDirection $direction,
        string $quantity,
        string $unitCost,
    ): array {
        return DB::transaction(function () use ($companyId, $branchId, $warehouseId, $productId, $variantId, $lotId, $direction, $quantity, $unitCost) {
            $tuple = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'product_lot_id' => $lotId,
            ];

            // Ensure a row exists before locking it: lockForUpdate() only
            // locks rows that already exist, so the very first movement for
            // a tuple needs the row created first.
            InventoryBalance::query()->firstOrCreate($tuple, ['quantity' => 0, 'average_cost' => 0]);

            /** @var InventoryBalance $balance */
            $balance = InventoryBalance::query()->where($tuple)->lockForUpdate()->firstOrFail();

            $previous = $balance->quantity;
            $signedQuantity = $direction === InventoryMovementDirection::In ? $quantity : bcmul($quantity, '-1', 4);
            $resulting = bcadd($previous, $signedQuantity, 4);

            $averageCost = $direction === InventoryMovementDirection::In && bccomp($quantity, '0', 4) > 0
                ? $this->weightedAverageCost($previous, $balance->average_cost, $quantity, $unitCost)
                : $balance->average_cost;

            $balance->update(['quantity' => $resulting, 'average_cost' => $averageCost]);

            return ['previous' => $previous, 'resulting' => $resulting];
        });
    }

    /**
     * @return numeric-string
     */
    public function currentQuantity(int $warehouseId, int $productId, ?int $variantId = null, ?int $lotId = null): string
    {
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->where('product_lot_id', $lotId)
            ->first();

        return $balance !== null ? $balance->quantity : '0.0000';
    }

    /**
     * Recomputes every inventory_balances row from inventory_movements for a
     * company, replacing whatever was there. Used by the "rebuild" console
     * command when balances are suspected to have drifted.
     */
    public function rebuildForCompany(int $companyId): void
    {
        DB::transaction(function () use ($companyId) {
            InventoryBalance::query()->where('company_id', $companyId)->delete();

            InventoryMovement::query()
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->chunkById(500, function ($movements) {
                    foreach ($movements as $movement) {
                        $this->applyMovement(
                            $movement->company_id,
                            $movement->branch_id,
                            $movement->warehouse_id,
                            $movement->product_id,
                            $movement->product_variant_id,
                            $movement->product_lot_id,
                            $movement->direction,
                            $movement->quantity,
                            $movement->unit_cost,
                        );
                    }
                });
        });
    }

    /**
     * @param  numeric-string  $previousQuantity
     * @param  numeric-string  $previousAverageCost
     * @param  numeric-string  $incomingQuantity
     * @param  numeric-string  $incomingUnitCost
     * @return numeric-string
     */
    private function weightedAverageCost(string $previousQuantity, string $previousAverageCost, string $incomingQuantity, string $incomingUnitCost): string
    {
        $previousValue = bcmul($previousQuantity, $previousAverageCost, 6);
        $incomingValue = bcmul($incomingQuantity, $incomingUnitCost, 6);
        $totalQuantity = bcadd($previousQuantity, $incomingQuantity, 4);

        if (bccomp($totalQuantity, '0', 4) <= 0) {
            return $incomingUnitCost;
        }

        return bcdiv(bcadd($previousValue, $incomingValue, 6), $totalQuantity, 4);
    }

    /**
     * @return Builder<InventoryMovement>
     */
    public function kardexQuery(int $warehouseId, int $productId, ?int $variantId = null): Builder
    {
        return InventoryMovement::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }
}
