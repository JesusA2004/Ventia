<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\InventoryMovementType;
use App\Http\Controllers\Concerns\GuardsBranchAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductLotRequest;
use App\Http\Requests\Inventory\UpdateProductLotRequest;
use App\Http\Resources\ProductLotResource;
use App\Http\Resources\WarehouseResource;
use App\Models\ProductLot;
use App\Models\Warehouse;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductLotController extends Controller
{
    use GuardsBranchAccess;

    public function __construct(private readonly RecordInventoryMovementAction $recorder)
    {
        $this->authorizeResource(ProductLot::class, 'lot');
    }

    public function index(Request $request): Response
    {
        $lots = ProductLot::query()
            ->with('product:id,name,sku')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->whereHas(
                'product',
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"),
            )->orWhere('lot_number', 'like', "%{$search}%"))
            ->when($request->boolean('expiring_soon'), fn ($query) => $query->whereNotNull('expiration_date')->where('expiration_date', '<=', now()->addDays(30)))
            ->orderBy('expiration_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Inventory/Lots/Index', [
            'lots' => PaginatedResource::make($lots, ProductLotResource::class),
            'filters' => $request->only('search', 'expiring_soon'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Lots/Create', [
            'warehouseOptions' => WarehouseResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreProductLotRequest $request): RedirectResponse
    {
        $lot = DB::transaction(function () use ($request) {
            $lot = ProductLot::create($request->safe()->except(['initial_quantity', 'warehouse_id']));

            $quantity = $request->validated('initial_quantity');

            if ($quantity && (float) $quantity > 0) {
                $warehouse = Warehouse::query()->whereKey($request->validated('warehouse_id'))->firstOrFail();

                $this->assertBranchAccessible($request->user(), $warehouse->branch_id);

                $this->recorder->execute([
                    'company_id' => $lot->company_id,
                    'branch_id' => $warehouse->branch_id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $lot->product_id,
                    'product_variant_id' => $lot->product_variant_id,
                    'product_lot_id' => $lot->id,
                    'movement_type' => InventoryMovementType::Initial,
                    'quantity' => (string) $quantity,
                    'unit_cost' => (string) $lot->cost,
                    'reason' => "Inventario inicial de lote {$lot->lot_number}",
                    'performed_by' => $request->user()->id,
                ]);
            }

            return $lot;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lote creado correctamente.']);

        return to_route('inventory.lots.edit', $lot);
    }

    public function edit(ProductLot $lot): Response
    {
        return Inertia::render('Inventory/Lots/Edit', [
            'lot' => ProductLotResource::make($lot->load('product:id,name,sku')),
        ]);
    }

    public function update(UpdateProductLotRequest $request, ProductLot $lot): RedirectResponse
    {
        $lot->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lote actualizado correctamente.']);

        return to_route('inventory.lots.index');
    }

    public function destroy(ProductLot $lot): RedirectResponse
    {
        if ($lot->product->inventoryMovements()->where('product_lot_id', $lot->id)->exists()) {
            throw ValidationException::withMessages([
                'lot' => 'No se puede eliminar: el lote tiene movimientos de inventario.',
            ]);
        }

        $lot->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lote eliminado correctamente.']);

        return to_route('inventory.lots.index');
    }
}
