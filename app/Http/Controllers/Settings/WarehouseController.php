<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreWarehouseRequest;
use App\Http\Requests\Settings\UpdateWarehouseRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\WarehouseResource;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Warehouse::class, 'warehouse');
    }

    public function index(Request $request): Response
    {
        $warehouses = Warehouse::query()
            ->with('branch:id,name')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Warehouses/Index', [
            'warehouses' => PaginatedResource::make($warehouses, WarehouseResource::class),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Warehouses/Create', [
            'branches' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Almacén creado correctamente.']);

        return to_route('settings.warehouses.index');
    }

    public function edit(Warehouse $warehouse): Response
    {
        return Inertia::render('Admin/Warehouses/Edit', [
            'warehouse' => WarehouseResource::make($warehouse),
            'branches' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Almacén actualizado correctamente.']);

        return to_route('settings.warehouses.index');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Almacén eliminado correctamente.']);

        return to_route('settings.warehouses.index');
    }
}
