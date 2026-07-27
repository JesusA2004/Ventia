<?php

namespace App\Actions\Inventory;

use App\Enums\StockCountStatus;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryBalanceService;
use Illuminate\Support\Facades\DB;

class StartStockCountAction
{
    public function __construct(private readonly InventoryBalanceService $balances) {}

    /**
     * @param  list<array{product_id: int, product_variant_id?: int|null, product_lot_id?: int|null}>  $products
     */
    public function execute(Warehouse $warehouse, array $products, ?string $notes, User $user): StockCount
    {
        return DB::transaction(function () use ($warehouse, $products, $notes, $user) {
            $count = StockCount::query()->create([
                'company_id' => $warehouse->company_id,
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'folio' => $this->nextFolio($warehouse->company_id),
                'status' => StockCountStatus::Counting,
                'started_by' => $user->id,
                'notes' => $notes,
                'started_at' => now(),
            ]);

            foreach ($products as $product) {
                $expected = $this->balances->currentQuantity(
                    $warehouse->id,
                    $product['product_id'],
                    $product['product_variant_id'] ?? null,
                    $product['product_lot_id'] ?? null,
                );

                StockCountItem::query()->create([
                    'stock_count_id' => $count->id,
                    'product_id' => $product['product_id'],
                    'product_variant_id' => $product['product_variant_id'] ?? null,
                    'product_lot_id' => $product['product_lot_id'] ?? null,
                    'expected_quantity' => $expected,
                ]);
            }

            return $count->fresh('items');
        });
    }

    private function nextFolio(int $companyId): string
    {
        $count = StockCount::withoutGlobalScopes()->where('company_id', $companyId)->count();

        return sprintf('CF-%06d', $count + 1);
    }
}
