<?php

namespace App\Actions\Sales;

use App\Enums\SaleStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Inventory\InventoryBalanceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Recovers a suspended sale back into an editable cart. Re-validates price
 * and stock at the moment of recovery rather than trusting the frozen
 * snapshot — both may have drifted since the ticket was suspended. Rebuilds
 * the sale via CreateSaleAction (fresh folio) and discards the suspended
 * row, which never touched inventory or cash, so nothing to reverse.
 */
class ResumeSaleAction
{
    public function __construct(
        private readonly CreateSaleAction $createSale,
        private readonly InventoryBalanceService $balances,
    ) {}

    public function execute(Sale $sale, User $user): Sale
    {
        if ($sale->status !== SaleStatus::Suspended) {
            throw new InvalidStateTransitionException('la venta', $sale->status->value, 'recuperar');
        }

        return DB::transaction(function () use ($sale, $user) {
            $items = $sale->items->map(function (SaleItem $item) use ($sale) {
                $product = Product::query()->whereKey($item->product_id)->firstOrFail();

                if ($product->status->value !== 'active' || ! $product->visible_in_pos) {
                    throw new InvalidArgumentException("El producto «{$product->name}» ya no está disponible para venta.");
                }

                $available = $this->balances->currentQuantity($sale->warehouse_id, $item->product_id, $item->product_variant_id);

                if (bccomp($available, $item->quantity, 4) < 0 && ! $product->allows_negative_stock) {
                    throw new InvalidArgumentException("Stock insuficiente para «{$product->name}» al recuperar la venta.");
                }

                return [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                ];
            })->all();

            $fresh = $this->createSale->execute([
                'branch_id' => $sale->branch_id,
                'warehouse_id' => $sale->warehouse_id,
                'register_id' => $sale->register_id,
                'cash_session_id' => $sale->cash_session_id,
                'customer_id' => $sale->customer_id,
                'seller_id' => $sale->seller_id,
                'notes' => $sale->notes,
                'status' => SaleStatus::Draft,
                'items' => array_values($items),
            ], $user);

            $sale->items()->delete();
            $sale->delete();

            return $fresh;
        });
    }
}
