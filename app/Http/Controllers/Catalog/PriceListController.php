<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StorePriceListRequest;
use App\Http\Requests\Catalog\UpdatePriceListRequest;
use App\Http\Resources\PriceListResource;
use App\Models\PriceList;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PriceListController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PriceList::class, 'priceList');
    }

    public function index(Request $request): Response
    {
        $priceLists = PriceList::query()
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/PriceLists/Index', [
            'priceLists' => PaginatedResource::make($priceLists, PriceListResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/PriceLists/Create');
    }

    public function store(StorePriceListRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            if ($request->boolean('is_default')) {
                PriceList::query()->update(['is_default' => false]);
            }

            PriceList::create($request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lista de precios creada correctamente.']);

        return to_route('catalog.price-lists.index');
    }

    public function edit(PriceList $priceList): Response
    {
        return Inertia::render('Catalog/PriceLists/Edit', [
            'priceList' => PriceListResource::make($priceList),
        ]);
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList): RedirectResponse
    {
        DB::transaction(function () use ($request, $priceList) {
            if ($request->boolean('is_default')) {
                PriceList::query()->whereKeyNot($priceList->id)->update(['is_default' => false]);
            }

            $priceList->update($request->validated());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lista de precios actualizada correctamente.']);

        return to_route('catalog.price-lists.index');
    }

    public function destroy(PriceList $priceList): RedirectResponse
    {
        if ($priceList->prices()->exists()) {
            throw ValidationException::withMessages([
                'priceList' => 'No se puede eliminar: la lista tiene precios de producto asignados.',
            ]);
        }

        $priceList->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lista de precios eliminada correctamente.']);

        return to_route('catalog.price-lists.index');
    }
}
