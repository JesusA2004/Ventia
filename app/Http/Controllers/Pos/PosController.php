<?php

namespace App\Http\Controllers\Pos;

use App\Enums\CashSessionStatus;
use App\Enums\TrackingType;
use App\Http\Controllers\Controller;
use App\Http\Resources\CashSessionResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PaymentMethodResource;
use App\Http\Resources\ProductResource;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\ActiveCompanyContext;
use App\Services\Inventory\InventoryBalanceService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(
        private readonly InventoryBalanceService $balances,
        private readonly SettingsService $settings,
        private readonly ActiveCompanyContext $activeCompany,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        abort_unless($request->user()->can('pos.access'), 403);

        $user = $request->user();
        $companyId = $this->activeCompany->requireCompanyId();

        $session = CashSession::query()
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Open)
            ->with('register:id,name,warehouse_id,branch_id')
            ->first();

        $requiresOpenRegister = (bool) ($this->settings->get($companyId, 'require_open_register') ?? true);

        if ($session === null && $requiresOpenRegister) {
            return to_route('cash.sessions.create');
        }

        $generalPublic = Customer::query()->where('customer_type', 'general_public')->first();

        return Inertia::render('Pos/Index', [
            'session' => $session ? CashSessionResource::make($session) : null,
            'warehouseId' => $session?->register?->warehouse_id,
            'defaultCustomer' => $generalPublic ? CustomerResource::make($generalPublic) : null,
            'categoryOptions' => CategoryResource::collection(Category::query()->where('status', 'active')->orderBy('name')->get()),
            'paymentMethodOptions' => PaymentMethodResource::collection(PaymentMethod::query()->where('status', 'active')->orderBy('sort_order')->get()),
            'favoriteProducts' => ProductResource::collection(
                Product::query()->where('status', 'active')->where('visible_in_pos', true)->where('is_favorite', true)->with(['unit', 'barcodes'])->limit(24)->get()
            ),
            'maxDiscountPercentage' => (string) ($this->settings->get($companyId, 'max_discount_percentage_without_authorization') ?? 10),
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('pos.access'), 403);

        $search = $request->string('search')->toString();
        $categoryId = $request->integer('category_id') ?: null;
        $warehouseId = $request->integer('warehouse_id');

        $products = Product::query()
            ->where('status', 'active')
            ->where('visible_in_pos', true)
            ->when($categoryId, fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('internal_code', 'like', "%{$search}%")
                ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', $search))
                ->orWhereHas('variants.barcodes', fn ($bq) => $bq->where('barcode', $search))))
            ->with(['unit', 'barcodes', 'variants.barcodes', 'variants.attributeValues.attribute'])
            ->limit(30)
            ->get();

        return response()->json([
            'data' => $products->map(fn (Product $product) => $this->productPayload($product, $warehouseId)),
        ]);
    }

    /**
     * Exact barcode match used by the scanner input: enter/scan should add
     * the item immediately instead of showing a results list.
     */
    public function lookupBarcode(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('pos.access'), 403);

        $barcode = $request->string('barcode')->toString();
        $warehouseId = $request->integer('warehouse_id');

        $product = Product::query()
            ->where('status', 'active')
            ->where('visible_in_pos', true)
            ->where(fn ($q) => $q
                ->whereHas('barcodes', fn ($bq) => $bq->where('barcode', $barcode))
                ->orWhereHas('variants.barcodes', fn ($bq) => $bq->where('barcode', $barcode)))
            ->with(['unit', 'barcodes', 'variants.barcodes', 'variants.attributeValues.attribute'])
            ->first();

        if ($product === null) {
            return response()->json(['data' => null], 404);
        }

        $matchedVariantId = $product->variants
            ->first(fn ($variant) => $variant->barcodes->firstWhere('barcode', $barcode) !== null)
            ?->id;

        return response()->json([
            'data' => $this->productPayload($product, $warehouseId, $matchedVariantId),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product, int $warehouseId, ?int $matchedVariantId = null): array
    {
        $payload = ProductResource::make($product)->resolve();
        $payload['matched_variant_id'] = $matchedVariantId;
        $payload['stock'] = $product->tracking_type === TrackingType::Variants
            ? null
            : $this->balances->currentQuantity($warehouseId, $product->id);

        $variants = [];
        foreach ($product->variants as $variant) {
            $attributes = [];
            foreach ($variant->attributeValues as $value) {
                $attributes[] = ['attribute' => $value->attribute->name, 'value' => $value->value];
            }

            $variants[] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'sale_price' => (string) $variant->sale_price,
                'status' => $variant->status->value,
                'attributes' => $attributes,
                'stock' => $this->balances->currentQuantity($warehouseId, $product->id, $variant->id),
            ];
        }

        $payload['variants'] = $variants;

        return $payload;
    }
}
