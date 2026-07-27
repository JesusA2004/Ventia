<?php

namespace App\Actions\Sales;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;

/**
 * Parks a cart without touching inventory or cash. Reuses CreateSaleAction
 * for identical pricing/validation, just with a different terminal status.
 */
class SuspendSaleAction
{
    public function __construct(private readonly CreateSaleAction $createSale) {}

    /**
     * @param  array{
     *     branch_id: int, warehouse_id: int, register_id: int, cash_session_id?: int|null,
     *     customer_id: int, seller_id?: int|null, notes?: string|null,
     *     items: list<array{
     *         product_id: int, product_variant_id?: int|null, quantity: numeric-string,
     *         discount_type?: string|null, discount_value?: numeric-string|null, notes?: string|null,
     *     }>,
     * }  $data
     */
    public function execute(array $data, User $cashier): Sale
    {
        return $this->createSale->execute([...$data, 'status' => SaleStatus::Suspended], $cashier);
    }
}
