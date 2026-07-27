<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Pricing\ChangeProductCostAction;
use App\Actions\Pricing\ChangeProductPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ChangeProductCostRequest;
use App\Http\Requests\Catalog\ChangeProductPriceRequest;
use App\Http\Resources\ProductPriceHistoryResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductPriceController extends Controller
{
    public function __construct(
        private readonly ChangeProductPriceAction $changePrice,
        private readonly ChangeProductCostAction $changeCost,
    ) {}

    public function history(Request $request, Product $product): Response
    {
        $this->authorize('viewPriceHistory', $product);

        $history = $product->priceHistories()
            ->with(['branch:id,name', 'priceList:id,name', 'changedByUser:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Products/PriceHistory', [
            'product' => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku],
            'history' => PaginatedResource::make($history, ProductPriceHistoryResource::class),
        ]);
    }

    public function updatePrice(ChangeProductPriceRequest $request, Product $product): RedirectResponse
    {
        $variant = $this->resolveVariant($product, $request->validated('product_variant_id'));

        $this->changePrice->execute(
            $product,
            $variant,
            (string) $request->validated('price'),
            $request->validated('reason'),
            $request->user(),
            $request->validated('branch_id'),
            $request->validated('price_list_id'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Precio actualizado y registrado en el historial.']);

        return back();
    }

    public function updateCost(ChangeProductCostRequest $request, Product $product): RedirectResponse
    {
        $variant = $this->resolveVariant($product, $request->validated('product_variant_id'));

        $this->changeCost->execute(
            $product,
            $variant,
            (string) $request->validated('cost'),
            $request->validated('reason'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Costo actualizado y registrado en el historial.']);

        return back();
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        return $variantId !== null ? $product->variants()->whereKey($variantId)->firstOrFail() : null;
    }
}
