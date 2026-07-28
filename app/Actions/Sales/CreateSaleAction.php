<?php

namespace App\Actions\Sales;

use App\Enums\SaleStatus;
use App\Enums\TaxType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use App\Services\ActiveCompanyContext;
use App\Services\Pricing\ProductPriceResolverService;
use App\Services\Sales\CalculateSaleTotalsService;
use App\Services\Sales\SaleFolioService;
use App\Services\SettingsService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Builds a Sale + SaleItems from a POS cart payload, resolving every price,
 * discount and tax server-side. Deliberately does NOT touch inventory or
 * cash — that's CompleteSaleAction's job, so this class is also reusable by
 * SuspendSaleAction (which needs the same pricing snapshot without deducting
 * stock).
 */
class CreateSaleAction
{
    public function __construct(
        private readonly ProductPriceResolverService $priceResolver,
        private readonly CalculateSaleTotalsService $totals,
        private readonly SaleFolioService $folios,
        private readonly SettingsService $settings,
        private readonly ActiveCompanyContext $activeCompany,
    ) {}

    /**
     * @param  array{
     *     branch_id: int, warehouse_id: int, register_id: int, cash_session_id?: int|null,
     *     customer_id: int, seller_id?: int|null, notes?: string|null, checkout_uuid?: string|null,
     *     status?: SaleStatus,
     *     general_discount?: array{type: string, value: numeric-string}|null,
     *     items: list<array{
     *         product_id: int, product_variant_id?: int|null, quantity: numeric-string,
     *         discount_type?: string|null, discount_value?: numeric-string|null, notes?: string|null,
     *     }>,
     * }  $data
     */
    public function execute(array $data, User $cashier): Sale
    {
        return DB::transaction(function () use ($data, $cashier) {
            $companyId = $this->activeCompany->requireCompanyId();
            $customer = Customer::query()->whereKey($data['customer_id'])->firstOrFail();

            if ($customer->company_id !== $companyId) {
                throw new InvalidArgumentException('El cliente no pertenece a esta empresa.');
            }

            if ($data['items'] === []) {
                throw new InvalidArgumentException('La venta debe tener al menos un producto.');
            }

            $maxDiscountPercent = Decimal::of((string) ($this->settings->get($companyId, 'max_discount_percentage_without_authorization') ?? 10));
            $canAuthorizeDiscounts = $cashier->can('discounts.authorize');

            $lines = [];
            foreach ($data['items'] as $item) {
                $lines[] = $this->buildLine($item, $companyId, $customer, $maxDiscountPercent, $canAuthorizeDiscounts, $cashier);
            }

            $aggregate = $this->totals->aggregate($lines);

            [$discountTotal, $total] = $this->applyGeneralDiscount(
                $aggregate,
                $data['general_discount'] ?? null,
                $maxDiscountPercent,
                $canAuthorizeDiscounts,
            );

            $sale = Sale::query()->create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'register_id' => $data['register_id'],
                'cash_session_id' => $data['cash_session_id'] ?? null,
                'customer_id' => $customer->id,
                'cashier_id' => $cashier->id,
                'seller_id' => $data['seller_id'] ?? null,
                'folio' => $this->folios->next($companyId),
                'status' => $data['status'] ?? SaleStatus::Draft,
                'subtotal' => $aggregate['subtotal'],
                'discount_total' => $discountTotal,
                'tax_total' => $aggregate['tax_total'],
                'total' => $total,
                'cost_total' => $aggregate['cost_total'],
                'profit_total' => bcsub($aggregate['subtotal'], $aggregate['cost_total'], 4),
                'checkout_uuid' => $data['checkout_uuid'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create($line['item']);
            }

            return $sale->load('items');
        });
    }

    /**
     * @param  array{product_id: int, product_variant_id?: int|null, quantity: numeric-string, discount_type?: string|null, discount_value?: numeric-string|null, notes?: string|null}  $item
     * @param  numeric-string  $maxDiscountPercent
     * @return array{item: array<string, mixed>, subtotal: numeric-string, discount_amount: numeric-string, tax_amount: numeric-string, total: numeric-string, unit_cost: numeric-string, quantity: numeric-string}
     */
    private function buildLine(array $item, int $companyId, Customer $customer, string $maxDiscountPercent, bool $canAuthorizeDiscounts, User $cashier): array
    {
        $product = Product::query()->with(['tax', 'unit', 'barcodes'])->whereKey($item['product_id'])->firstOrFail();

        if ($product->company_id !== $companyId) {
            throw new InvalidArgumentException('Uno de los productos no pertenece a esta empresa.');
        }

        if ($product->status->value !== 'active' || ! $product->visible_in_pos) {
            throw new InvalidArgumentException("El producto «{$product->name}» no está disponible para venta.");
        }

        $variant = null;
        if (! empty($item['product_variant_id'])) {
            $variant = ProductVariant::query()->whereKey($item['product_variant_id'])->firstOrFail();

            if ($variant->company_id !== $companyId || $variant->product_id !== $product->id) {
                throw new InvalidArgumentException('La variante seleccionada no corresponde a este producto.');
            }
        }

        $quantity = Decimal::of((string) $item['quantity']);

        if (bccomp($quantity, '0', 4) <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        if (! $product->unit->allows_fraction && $this->hasFractionalPart($quantity)) {
            throw new InvalidArgumentException("El producto «{$product->name}» se vende por pieza y no admite cantidades decimales.");
        }

        $unitPrice = Decimal::of($this->priceResolver->resolve($product, $variant, $customer->branch_id, $customer->price_list_id, $quantity));
        $unitCost = (string) ($variant ?? $product)->cost;
        $minimumPrice = ($variant ?? $product)->minimum_price;

        $gross = bcmul($quantity, $unitPrice, 4);
        $discountAmount = $this->resolveDiscount($item['discount_type'] ?? null, $item['discount_value'] ?? null, $gross);

        if (bccomp($discountAmount, '0', 4) > 0 && ! $cashier->can('discounts.apply')) {
            throw new InvalidArgumentException('No tienes permiso para aplicar descuentos.');
        }

        $discountPercent = bccomp($gross, '0', 4) > 0 ? bcmul(bcdiv($discountAmount, $gross, 6), '100', 4) : '0.0000';

        if (! $canAuthorizeDiscounts && bccomp($discountPercent, $maxDiscountPercent, 4) > 0) {
            throw new InvalidArgumentException("El descuento en «{$product->name}» excede el máximo permitido sin autorización ({$maxDiscountPercent}%).");
        }

        $netUnitPrice = bcdiv(bcsub($gross, $discountAmount, 4), $quantity, 4);

        if ($minimumPrice !== null && bccomp($netUnitPrice, (string) $minimumPrice, 4) < 0 && ! $canAuthorizeDiscounts) {
            throw new InvalidArgumentException("El descuento en «{$product->name}» deja el precio por debajo del mínimo permitido.");
        }

        $tax = $product->tax;
        $computed = $this->totals->calculateLine(
            $quantity,
            $unitPrice,
            $discountAmount,
            $tax !== null ? (string) $tax->rate : '0.0000',
            $tax !== null ? $tax->type : TaxType::Exempt,
            $tax !== null && $tax->included_in_price,
        );

        $primaryBarcode = ($variant ? $variant->barcodes()->where('is_primary', true)->first() : null)
            ?? $product->barcodes->firstWhere('is_primary', true);

        return [
            'item' => [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name_snapshot' => $product->name,
                'sku_snapshot' => ($variant ?? $product)->sku,
                'barcode_snapshot' => $primaryBarcode?->barcode,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'original_unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'discount_amount' => $discountAmount,
                'tax_rate' => $tax !== null ? (string) $tax->rate : '0.0000',
                'tax_amount' => $computed['tax_amount'],
                'subtotal' => $computed['subtotal'],
                'total' => $computed['total'],
                'notes' => $item['notes'] ?? null,
            ],
            'subtotal' => $computed['subtotal'],
            'discount_amount' => $discountAmount,
            'tax_amount' => $computed['tax_amount'],
            'total' => $computed['total'],
            'unit_cost' => $unitCost,
            'quantity' => $quantity,
        ];
    }

    private function hasFractionalPart(string $quantity): bool
    {
        $parts = explode('.', $quantity);

        return isset($parts[1]) && rtrim($parts[1], '0') !== '';
    }

    /**
     * @param  numeric-string  $gross
     * @return numeric-string
     */
    private function resolveDiscount(?string $type, ?string $value, string $gross): string
    {
        if ($type === null || $value === null) {
            return '0.0000';
        }

        $value = Decimal::of($value);

        if (bccomp($value, '0', 4) < 0) {
            throw new InvalidArgumentException('El descuento no puede ser negativo.');
        }

        $amount = $type === 'percentage' ? bcmul($gross, bcdiv($value, '100', 6), 4) : $value;

        return Decimal::of(bccomp($amount, $gross, 4) > 0 ? $gross : $amount);
    }

    /**
     * @param  array{subtotal: numeric-string, discount_total: numeric-string, tax_total: numeric-string, total: numeric-string, cost_total: numeric-string, profit_total: numeric-string}  $aggregate
     * @param  array{type: string, value: numeric-string}|null  $generalDiscount
     * @param  numeric-string  $maxDiscountPercent
     * @return array{0: numeric-string, 1: numeric-string} [discount_total, total]
     */
    private function applyGeneralDiscount(array $aggregate, ?array $generalDiscount, string $maxDiscountPercent, bool $canAuthorizeDiscounts): array
    {
        if ($generalDiscount === null) {
            return [$aggregate['discount_total'], $aggregate['total']];
        }

        $value = Decimal::of((string) $generalDiscount['value']);

        if (bccomp($value, '0', 4) < 0) {
            throw new InvalidArgumentException('El descuento general no puede ser negativo.');
        }

        $itemsTotal = $aggregate['total'];
        $amount = $generalDiscount['type'] === 'percentage' ? bcmul($itemsTotal, bcdiv($value, '100', 6), 4) : $value;
        $amount = bccomp($amount, $itemsTotal, 4) > 0 ? $itemsTotal : $amount;

        $percent = bccomp($itemsTotal, '0', 4) > 0 ? bcmul(bcdiv($amount, $itemsTotal, 6), '100', 4) : '0.0000';

        if (! $canAuthorizeDiscounts && bccomp($percent, $maxDiscountPercent, 4) > 0) {
            throw new InvalidArgumentException("El descuento general excede el máximo permitido sin autorización ({$maxDiscountPercent}%).");
        }

        $newTotal = bcsub($itemsTotal, $amount, 4);

        if (bccomp($newTotal, '0', 4) < 0) {
            throw new InvalidArgumentException('El descuento no puede dejar la venta en un total negativo.');
        }

        return [bcadd($aggregate['discount_total'], $amount, 4), $newTotal];
    }
}
