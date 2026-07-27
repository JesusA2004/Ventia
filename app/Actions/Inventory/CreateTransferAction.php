<?php

namespace App\Actions\Inventory;

use App\Enums\StockTransferStatus;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateTransferAction
{
    /**
     * @param  list<array{product_id: int, product_variant_id?: int|null, product_lot_id?: int|null, quantity_requested: string}>  $items
     */
    public function execute(Warehouse $origin, Warehouse $destination, array $items, ?string $notes, User $user): StockTransfer
    {
        if ($origin->id === $destination->id) {
            throw new InvalidArgumentException('El almacén de origen y destino no pueden ser el mismo.');
        }

        if ($origin->company_id !== $destination->company_id) {
            throw new InvalidArgumentException('Ambos almacenes deben pertenecer a la misma empresa.');
        }

        return DB::transaction(function () use ($origin, $destination, $items, $notes, $user) {
            $transfer = StockTransfer::query()->create([
                'company_id' => $origin->company_id,
                'origin_branch_id' => $origin->branch_id,
                'origin_warehouse_id' => $origin->id,
                'destination_branch_id' => $destination->branch_id,
                'destination_warehouse_id' => $destination->id,
                'folio' => $this->nextFolio($origin->company_id),
                'status' => StockTransferStatus::Draft,
                'requested_by' => $user->id,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                StockTransferItem::query()->create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_lot_id' => $item['product_lot_id'] ?? null,
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            return $transfer->fresh('items');
        });
    }

    private function nextFolio(int $companyId): string
    {
        $count = StockTransfer::withoutGlobalScopes()->where('company_id', $companyId)->count();

        return sprintf('TR-%06d', $count + 1);
    }
}
