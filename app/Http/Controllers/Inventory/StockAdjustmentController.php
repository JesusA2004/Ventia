<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\GuardsBranchAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    use GuardsBranchAccess;

    public function __construct(
        private readonly AdjustStockAction $adjustStock,
        private readonly AuditLogger $audit,
    ) {
        $this->middleware('can:inventory.adjust');
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Adjustments/Create', [
            'warehouseOptions' => WarehouseResource::collection(Warehouse::query()->orderBy('name')->get()),
            'movementTypes' => collect(InventoryMovementType::manualAdjustmentTypes())
                ->map(fn (InventoryMovementType $type) => ['value' => $type->value, 'label' => $type->label()]),
        ]);
    }

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $warehouse = Warehouse::query()->whereKey($request->validated('warehouse_id'))->firstOrFail();

        $this->assertBranchAccessible($request->user(), $warehouse->branch_id);

        $product = Product::query()->whereKey($request->validated('product_id'))->firstOrFail();
        $variant = $request->validated('product_variant_id')
            ? ProductVariant::query()->whereKey($request->validated('product_variant_id'))->firstOrFail()
            : null;
        $lot = $request->validated('product_lot_id')
            ? ProductLot::query()->whereKey($request->validated('product_lot_id'))->firstOrFail()
            : null;

        try {
            $this->adjustStock->execute(
                $warehouse,
                $product,
                $variant,
                $lot,
                InventoryMovementType::from($request->validated('movement_type')),
                (string) $request->validated('quantity'),
                $request->validated('reason'),
                $request->validated('notes'),
                $request->user(),
            );
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        $movementLabel = InventoryMovementType::from($request->validated('movement_type'))->label();
        $this->audit->log(
            'inventory', 'adjusted',
            "Realizó un ajuste de inventario ({$movementLabel}) de {$request->validated('quantity')} en «{$product->name}» ({$product->sku}), almacén «{$warehouse->name}».",
            $product,
            newValues: ['warehouse' => $warehouse->name, 'movement_type' => $request->validated('movement_type'), 'quantity' => $request->validated('quantity')],
            reason: $request->validated('reason'),
            branchId: $warehouse->branch_id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ajuste de inventario registrado correctamente.']);

        return to_route('inventory.balances.index');
    }
}
