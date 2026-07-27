<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreBrandRequest;
use App\Http\Requests\Catalog\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Brand::class, 'brand');
    }

    public function index(Request $request): Response
    {
        $brands = Brand::query()
            ->withCount('products')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Brands/Index', [
            'brands' => PaginatedResource::make($brands, BrandResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Brands/Create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::create([...$request->validated(), 'slug' => str($request->validated('name'))->slug()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca creada correctamente.']);

        return to_route('catalog.brands.index');
    }

    public function edit(Brand $brand): Response
    {
        return Inertia::render('Catalog/Brands/Edit', [
            'brand' => BrandResource::make($brand),
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update([...$request->validated(), 'slug' => str($request->validated('name'))->slug()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca actualizada correctamente.']);

        return to_route('catalog.brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'brand' => 'No se puede eliminar: la marca tiene productos relacionados.',
            ]);
        }

        $brand->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca eliminada correctamente.']);

        return to_route('catalog.brands.index');
    }
}
