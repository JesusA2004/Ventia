<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreUnitRequest;
use App\Http\Requests\Catalog\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
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
        $unit = Unit::create($request->validated());

        $this->audit->log('units', 'created', "Creó la unidad «{$unit->name}».", $unit);

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
        $before = $unit->only(['name', 'symbol', 'allows_fraction', 'base_unit_id']);
        $unit->update($request->validated());

        $this->audit->log('units', 'updated', "Actualizó la unidad «{$unit->name}».", $unit, $before, $unit->only(['name', 'symbol', 'allows_fraction', 'base_unit_id']));

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

        $name = $unit->name;
        $unit->delete();

        $this->audit->log('units', 'deleted', "Eliminó la unidad «{$name}».", $unit);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unidad eliminada correctamente.']);

        return to_route('catalog.units.index');
    }
}
