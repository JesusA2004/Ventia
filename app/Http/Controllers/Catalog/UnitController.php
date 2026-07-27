<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreUnitRequest;
use App\Http\Requests\Catalog\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Unit::class, 'unit');
    }

    public function index(Request $request): Response
    {
        $units = Unit::query()
            ->withCount('products')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalog/Units/Index', [
            'units' => PaginatedResource::make($units, UnitResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Units/Create', [
            'baseUnitOptions' => UnitResource::collection(Unit::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad creada correctamente.']);

        return to_route('catalog.units.index');
    }

    public function edit(Unit $unit): Response
    {
        return Inertia::render('Catalog/Units/Edit', [
            'unit' => UnitResource::make($unit),
            'baseUnitOptions' => UnitResource::collection(Unit::query()->whereKeyNot($unit->id)->orderBy('name')->get()),
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad actualizada correctamente.']);

        return to_route('catalog.units.index');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'unit' => 'No se puede eliminar: la unidad tiene productos relacionados.',
            ]);
        }

        $unit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad eliminada correctamente.']);

        return to_route('catalog.units.index');
    }
}
