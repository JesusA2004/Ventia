<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActiveCompanyContext;
use App\Services\Pricing\ProductPriceResolverService;
use App\Services\Promotions\PromotionEligibilityService;
use App\Support\Decimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Lets the POS show "promoción aplicada" / validate a coupon code as the
 * cart changes, before the cashier commits to checkout. Uses the exact same
 * PromotionEligibilityService CreateSaleAction calls at commit time, so
 * what's previewed here never disagrees with what actually gets charged —
 * this endpoint just estimates line totals (no per-line manual discount or
 * tax nuance) since that estimate is never trusted for the real charge.
 */
class PromotionPreviewController extends Controller
{
    public function __construct(
        private readonly PromotionEligibilityService $eligibility,
        private readonly ProductPriceResolverService $priceResolver,
        private readonly ActiveCompanyContext $activeCompany,
    ) {
        $this->middleware('can:pos.access');
    }

    public function __invoke(Request $request): JsonResponse
    {
        $companyId = $this->activeCompany->requireCompanyId();

        $data = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $customer = Customer::query()->whereKey($data['customer_id'])->firstOrFail();

        $lines = [];
        foreach ($data['items'] as $item) {
            $product = Product::query()->whereKey($item['product_id'])->first();

            if ($product === null || $product->company_id !== $companyId) {
                continue;
            }

            $variant = null;
            if (! empty($item['product_variant_id'])) {
                $variant = ProductVariant::query()->whereKey($item['product_variant_id'])->first();
            }

            $quantity = Decimal::of((string) $item['quantity']);
            $unitPrice = Decimal::of($this->priceResolver->resolve($product, $variant, $customer->branch_id, $customer->price_list_id, $quantity));

            $lines[] = [
                'product_id' => $product->id,
                'category_id' => $product->category_id,
                'quantity' => $quantity,
                'total' => bcmul($quantity, $unitPrice, 4),
            ];
        }

        if ($lines === []) {
            return response()->json(['data' => ['cart_total' => '0.0000', 'promotion' => null, 'coupon' => null, 'coupon_error' => null]]);
        }

        $result = $this->eligibility->resolve($companyId, (int) $data['branch_id'], $customer, $lines, $data['coupon_code'] ?? null);

        return response()->json(['data' => $result]);
    }
}
