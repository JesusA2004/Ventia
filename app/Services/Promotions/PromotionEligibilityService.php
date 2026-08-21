<?php

namespace App\Services\Promotions;

use App\Enums\CustomerType;
use App\Enums\SaleStatus;
use App\Enums\Status;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\Sale;
use App\Support\Decimal;
use Illuminate\Support\Collection;

/**
 * The single place that decides which promotion/coupon (if any) applies to
 * a cart — used identically by the POS "eligible promotions" preview and by
 * CreateSaleAction at checkout, so what the cashier sees is exactly what
 * gets charged. Never trust a frontend-computed discount amount; this is
 * always re-run server-side against the real cart at commit time.
 *
 * Combination rule (kept deliberately simple — see Promotion::combinable
 * docblock): at most one automatic promotion applies (the eligible one with
 * the highest priority). If a coupon is also submitted and validates, it
 * replaces the promotion unless both the coupon AND the chosen promotion
 * are combinable, in which case both apply together.
 */
class PromotionEligibilityService
{
    /**
     * @param  list<array{product_id: int, category_id: ?int, quantity: numeric-string, total: numeric-string}>  $lines
     * @return array{
     *     cart_total: numeric-string,
     *     promotion: array{id: int, name: string, discount_amount: numeric-string}|null,
     *     coupon: array{id: int, code: string, name: string, discount_amount: numeric-string}|null,
     *     coupon_error: string|null,
     * }
     */
    public function resolve(int $companyId, int $branchId, ?Customer $customer, array $lines, ?string $couponCode): array
    {
        $cartTotal = '0.0000';

        foreach ($lines as $line) {
            $cartTotal = bcadd($cartTotal, $line['total'], 4);
        }

        $promotion = $this->bestEligiblePromotion($companyId, $branchId, $customer, $lines, $cartTotal);

        $couponError = null;
        $coupon = null;

        if ($couponCode !== null && trim($couponCode) !== '') {
            [$coupon, $couponError] = $this->evaluateCoupon($companyId, $branchId, $customer, $lines, $cartTotal, $couponCode);
        }

        if ($coupon !== null) {
            $keepPromotion = $promotion !== null && $coupon['combinable'] && $promotion['combinable'];
            $promotion = $keepPromotion ? $promotion : null;
        }

        return [
            'cart_total' => $cartTotal,
            'promotion' => $promotion !== null ? ['id' => $promotion['id'], 'name' => $promotion['name'], 'discount_amount' => $promotion['discount_amount']] : null,
            'coupon' => $coupon !== null ? ['id' => $coupon['id'], 'code' => $coupon['code'], 'name' => $coupon['name'], 'discount_amount' => $coupon['discount_amount']] : null,
            'coupon_error' => $couponError,
        ];
    }

    /**
     * @param  list<array{product_id: int, category_id: ?int, quantity: numeric-string, total: numeric-string}>  $lines
     * @param  numeric-string  $cartTotal
     * @return array{id: int, name: string, discount_amount: numeric-string, combinable: bool}|null
     */
    private function bestEligiblePromotion(int $companyId, int $branchId, ?Customer $customer, array $lines, string $cartTotal): ?array
    {
        $promotions = Promotion::query()
            ->where('company_id', $companyId)
            ->where('status', Status::Active)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('priority')
            ->with(['branches:id', 'products:id', 'categories:id'])
            ->get();

        $best = null;
        $bestPriority = null;

        foreach ($promotions as $promotion) {
            // Promotions are ordered by priority desc: once a tier has
            // produced an eligible match, a lower tier can never outrank it.
            if ($bestPriority !== null && $promotion->priority < $bestPriority) {
                break;
            }

            if ($promotion->branches->isNotEmpty() && ! $promotion->branches->contains('id', $branchId)) {
                continue;
            }

            [$matchingSubtotal, $matchingQuantity] = $this->matchingLines($lines, $promotion->products->pluck('id'), $promotion->categories->pluck('id'));

            if (bccomp($matchingSubtotal, '0', 4) <= 0) {
                continue;
            }

            if ($promotion->min_purchase_amount !== null && bccomp($cartTotal, Decimal::of((string) $promotion->min_purchase_amount), 4) < 0) {
                continue;
            }

            if ($promotion->min_quantity !== null && bccomp($matchingQuantity, Decimal::of((string) $promotion->min_quantity), 4) < 0) {
                continue;
            }

            if (! $this->withinUsageLimits($promotion->usage_limit, $promotion->usage_limit_per_customer, 'promotion_id', $promotion->id, $companyId, $customer)) {
                continue;
            }

            $discount = $promotion->type->amountOff(Decimal::of((string) $promotion->value), $matchingSubtotal);

            if (bccomp($discount, '0', 4) <= 0) {
                continue;
            }

            if ($best === null || bccomp($discount, $best['discount_amount'], 4) > 0) {
                $best = ['id' => $promotion->id, 'name' => $promotion->name, 'discount_amount' => $discount, 'combinable' => $promotion->combinable];
                $bestPriority = $promotion->priority;
            }
        }

        return $best;
    }

    /**
     * @param  list<array{product_id: int, category_id: ?int, quantity: numeric-string, total: numeric-string}>  $lines
     * @param  numeric-string  $cartTotal
     * @return array{0: array{id: int, code: string, name: string, discount_amount: numeric-string, combinable: bool}|null, 1: string|null}
     */
    private function evaluateCoupon(int $companyId, int $branchId, ?Customer $customer, array $lines, string $cartTotal, string $couponCode): array
    {
        $coupon = Coupon::query()
            ->where('company_id', $companyId)
            ->where('code', Coupon::normalizeCode($couponCode))
            ->with(['branches:id', 'products:id', 'categories:id'])
            ->first();

        if ($coupon === null) {
            return [null, 'Este cupón no existe.'];
        }

        if (! $coupon->isActiveNow()) {
            return [null, 'Este cupón no está vigente.'];
        }

        if ($coupon->branches->isNotEmpty() && ! $coupon->branches->contains('id', $branchId)) {
            return [null, 'Este cupón no aplica en esta sucursal.'];
        }

        [$matchingSubtotal] = $this->matchingLines($lines, $coupon->products->pluck('id'), $coupon->categories->pluck('id'));

        if (bccomp($matchingSubtotal, '0', 4) <= 0) {
            return [null, 'Este cupón no aplica a los productos del carrito.'];
        }

        if ($coupon->min_purchase_amount !== null && bccomp($cartTotal, Decimal::of((string) $coupon->min_purchase_amount), 4) < 0) {
            return [null, "La compra debe ser de al menos {$coupon->min_purchase_amount} para usar este cupón."];
        }

        if (! $this->withinUsageLimits($coupon->usage_limit, $coupon->usage_limit_per_customer, 'coupon_id', $coupon->id, $companyId, $customer)) {
            return [null, 'Este cupón ya alcanzó su límite de usos.'];
        }

        $discount = $coupon->type->amountOff(Decimal::of((string) $coupon->value), $matchingSubtotal);

        if (bccomp($discount, '0', 4) <= 0) {
            return [null, 'Este cupón no aplica a los productos del carrito.'];
        }

        return [
            ['id' => $coupon->id, 'code' => $coupon->code, 'name' => $coupon->name, 'discount_amount' => $discount, 'combinable' => $coupon->combinable],
            null,
        ];
    }

    /**
     * @param  list<array{product_id: int, category_id: ?int, quantity: numeric-string, total: numeric-string}>  $lines
     * @param  Collection<int, int>  $productIds
     * @param  Collection<int, int>  $categoryIds
     * @return array{0: numeric-string, 1: numeric-string} [matching subtotal, matching quantity]
     */
    private function matchingLines(array $lines, Collection $productIds, Collection $categoryIds): array
    {
        $scoped = $productIds->isNotEmpty() || $categoryIds->isNotEmpty();

        $subtotal = '0.0000';
        $quantity = '0.0000';

        foreach ($lines as $line) {
            $matches = ! $scoped
                || $productIds->contains($line['product_id'])
                || ($line['category_id'] !== null && $categoryIds->contains($line['category_id']));

            if ($matches) {
                $subtotal = bcadd($subtotal, $line['total'], 4);
                $quantity = bcadd($quantity, $line['quantity'], 4);
            }
        }

        return [$subtotal, $quantity];
    }

    /**
     * @param  'promotion_id'|'coupon_id'  $column
     */
    private function withinUsageLimits(?int $usageLimit, ?int $usageLimitPerCustomer, string $column, int $id, int $companyId, ?Customer $customer): bool
    {
        if ($usageLimit !== null) {
            $used = Sale::query()->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where($column, $id)
                ->where('status', SaleStatus::Completed)
                ->count();

            if ($used >= $usageLimit) {
                return false;
            }
        }

        // "Público general" is shared by every walk-in sale, so a per-customer
        // limit against it would really be a global limit in disguise — skip it.
        if ($usageLimitPerCustomer !== null && $customer !== null && $customer->customer_type !== CustomerType::GeneralPublic) {
            $usedByCustomer = Sale::query()->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where($column, $id)
                ->where('customer_id', $customer->id)
                ->where('status', SaleStatus::Completed)
                ->count();

            if ($usedByCustomer >= $usageLimitPerCustomer) {
                return false;
            }
        }

        return true;
    }
}
