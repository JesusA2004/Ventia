<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductAttributeRequest;
use App\Http\Requests\Catalog\UpdateProductAttributeRequest;
use App\Http\Resources\ProductAttributeResource;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductAttributeController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(ProductAttribute::class, 'attribute');
    }

    public function index(): Response
    {
        return Inertia::render('Products/Attributes/Index', [
            'attributes' => ProductAttributeResource::collection(
                ProductAttribute::query()->with('values')->orderBy('name')->get(),
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Attributes/Create');
    }

    public function store(StoreProductAttributeRequest $request): RedirectResponse
    {
        $attribute = DB::transaction(function () use ($request) {
            $attribute = ProductAttribute::create($request->safe()->except('values'));

            foreach ($request->validated('values', []) as $index => $value) {
                ProductAttributeValue::create([
                    'product_attribute_id' => $attribute->id,
                    'value' => $value['value'],
                    'sort_order' => $index,
                ]);
            }

            return $attribute;
        });

        $this->audit->log('products', 'attribute_created', "Creó el atributo «{$attribute->name}».", $attribute);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atributo creado correctamente.']);

        return to_route('products.attributes.index');
    }

    public function edit(ProductAttribute $attribute): Response
    {
        return Inertia::render('Products/Attributes/Edit', [
            'attribute' => ProductAttributeResource::make($attribute->load('values')),
        ]);
    }

    public function update(UpdateProductAttributeRequest $request, ProductAttribute $attribute): RedirectResponse
    {
        DB::transaction(function () use ($request, $attribute) {
            $attribute->update($request->safe()->except('values'));

            $keptIds = [];

            foreach ($request->validated('values', []) as $index => $valueData) {
                if (! empty($valueData['id'])) {
                    $value = $attribute->values()->whereKey($valueData['id'])->firstOrFail();
                    $value->update(['value' => $valueData['value'], 'sort_order' => $index]);
                    $keptIds[] = $value->id;

                    continue;
                }

                $value = ProductAttributeValue::create([
                    'product_attribute_id' => $attribute->id,
                    'value' => $valueData['value'],
                    'sort_order' => $index,
                ]);
                $keptIds[] = $value->id;
            }

            $attribute->values()->whereNotIn('id', $keptIds)->delete();
        });

        $this->audit->log('products', 'attribute_updated', "Actualizó el atributo «{$attribute->name}».", $attribute);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atributo actualizado correctamente.']);

        return to_route('products.attributes.index');
    }

    public function destroy(ProductAttribute $attribute): RedirectResponse
    {
        if ($attribute->values()->whereHas('variants')->exists()) {
            throw ValidationException::withMessages([
                'attribute' => 'No se puede eliminar: el atributo está en uso por una o más variantes.',
            ]);
        }

        $name = $attribute->name;
        $attribute->delete();

        $this->audit->log('products', 'attribute_deleted', "Eliminó el atributo «{$name}».", $attribute);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atributo eliminado correctamente.']);

        return to_route('products.attributes.index');
    }
}
