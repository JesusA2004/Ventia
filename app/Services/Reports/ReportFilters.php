<?php

namespace App\Services\Reports;

use Illuminate\Http\Request;

/**
 * Optional narrowing filters shared by every report tab. Each *ReportService
 * only applies the filters relevant to what it measures — an irrelevant
 * filter (e.g. payment method on the Inventario tab) is simply ignored, not
 * an error, since the Reportes screen only renders the filter controls that
 * make sense for the active tab. Built once in the controller and reused
 * identically by the screen, CSV, PDF and Excel exports.
 */
final readonly class ReportFilters
{
    public function __construct(
        public ?int $branchId = null,
        public ?int $registerId = null,
        public ?int $cashierId = null,
        public ?int $productId = null,
        public ?int $categoryId = null,
        public ?int $paymentMethodId = null,
        public ?int $customerId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            branchId: $request->integer('branch_id') ?: null,
            registerId: $request->integer('register_id') ?: null,
            cashierId: $request->integer('cashier_id') ?: null,
            productId: $request->integer('product_id') ?: null,
            categoryId: $request->integer('category_id') ?: null,
            paymentMethodId: $request->integer('payment_method_id') ?: null,
            customerId: $request->integer('customer_id') ?: null,
        );
    }

    /**
     * @return array{branch_id: ?int, register_id: ?int, cashier_id: ?int, product_id: ?int, category_id: ?int, payment_method_id: ?int, customer_id: ?int}
     */
    public function toArray(): array
    {
        return [
            'branch_id' => $this->branchId,
            'register_id' => $this->registerId,
            'cashier_id' => $this->cashierId,
            'product_id' => $this->productId,
            'category_id' => $this->categoryId,
            'payment_method_id' => $this->paymentMethodId,
            'customer_id' => $this->customerId,
        ];
    }
}
