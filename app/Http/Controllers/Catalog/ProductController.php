<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Products\CreateProductAction;
use App\Actions\Products\DuplicateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductAttributeResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TaxResource;
use App\Http\Resources\UnitResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\ActiveCompanyContext;
use App\Support\PaginatedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly CreateProductAction $createProduct,
        private readonly UpdateProductAction $updateProduct,
        private readonly DuplicateProductAction $duplicateProduct,
        private readonly ActiveCompanyContext $activeCompany,
    ) {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,name,symbol'])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('internal_code', 'like', "%{$search}%")
            ))
            ->when($request->integer('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->integer('brand_id'), fn ($query, $brandId) => $query->where('brand_id', $brandId))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => PaginatedResource::make($products, ProductResource::class),
            'filters' => $request->only('search', 'category_id', 'brand_id', 'status'),
            'categoryOptions' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'brandOptions' => BrandResource::collection(Brand::query()->orderBy('name')->get()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', $this->formOptions());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->createProduct->execute($request->validated(), $this->activeCompany->requireCompanyId());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto creado correctamente.']);

        return to_route('products.index');
    }

    public function edit(Product $product): Response
    {
        $product->load(['variants.attributeValues.attribute', 'barcodes']);

        return Inertia::render('Products/Edit', [
            'product' => ProductResource::make($product),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->updateProduct->execute($product, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto actualizado correctamente.']);

        return to_route('products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->inventoryMovements()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'No se puede eliminar: el producto tiene movimientos de inventario históricos. Desactívalo en su lugar.',
            ]);
        }

        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto eliminado correctamente.']);

        return to_route('products.index');
    }

    public function restore(int $product): RedirectResponse
    {
        $model = Product::withTrashed()->findOrFail($product);
        $this->authorize('restore', $model);

        $model->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto restaurado correctamente.']);

        return to_route('products.index');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $copy = $this->duplicateProduct->execute($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Producto duplicado como «{$copy->sku}». Revisa y actívalo."]);

        return to_route('products.edit', $copy);
    }

    /**
     * Lightweight product lookup shared by the inventory pickers (ajustes,
     * transferencias, conteos): search by name/SKU/barcode, with variants
     * eager loaded so the frontend can offer variant selection inline.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $search = $request->string('search')->toString();

        $products = Product::query()
            ->with('variants')
            ->where('status', 'active')
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('internal_code', 'like', "%{$search}%")
                ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', $search)))
            ->limit(15)
            ->get();

        return response()->json(ProductResource::collection($products));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categoryOptions' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'brandOptions' => BrandResource::collection(Brand::query()->orderBy('name')->get()),
            'unitOptions' => UnitResource::collection(Unit::query()->orderBy('name')->get()),
            'taxOptions' => TaxResource::collection(Tax::query()->orderBy('name')->get()),
            'attributeOptions' => ProductAttributeResource::collection(ProductAttribute::query()->with('values')->orderBy('name')->get()),
        ];
    }
}
