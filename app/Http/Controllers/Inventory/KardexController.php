<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\WarehouseResource;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryBalanceService;
use App\Support\PaginatedResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class KardexController extends Controller
{
    public function __construct(private readonly InventoryBalanceService $balances)
    {
        $this->middleware('can:inventory.kardex');
    }

    public function index(Request $request): Response
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $productId = $request->integer('product_id') ?: null;
        $variantId = $request->integer('product_variant_id') ?: null;

        $movements = null;
        $totals = null;

        if ($warehouseId && $productId) {
            $query = $this->balances->kardexQuery($warehouseId, $productId, $variantId);

            if ($from = $request->date('from')) {
                $query->where('occurred_at', '>=', $from);
            }

            if ($to = $request->date('to')) {
                $query->where('occurred_at', '<=', $to->endOfDay());
            }

            $totals = (clone $query)->selectRaw("
                    COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE 0 END), 0) as total_in,
                    COALESCE(SUM(CASE WHEN direction = 'out' THEN quantity ELSE 0 END), 0) as total_out
                ")->toBase()->first();

            $movements = PaginatedResource::make(
                $query->with('performedByUser:id,name')->paginate(30)->withQueryString(),
                InventoryMovementResource::class,
            );
        }

        return Inertia::render('Inventory/Kardex/Index', [
            'movements' => $movements,
            'totals' => $totals ? ['in' => (string) $totals->total_in, 'out' => (string) $totals->total_out] : null,
            'filters' => $request->only('warehouse_id', 'product_id', 'product_variant_id', 'from', 'to'),
            'warehouseOptions' => WarehouseResource::collection(Warehouse::query()->orderBy('name')->get()),
            'productOptions' => $productId ? ProductResource::collection(Product::query()->whereKey($productId)->get()) : [],
        ]);
    }

    public function export(Request $request): HttpResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        $productId = $request->integer('product_id');
        $variantId = $request->integer('product_variant_id') ?: null;

        $query = $this->balances->kardexQuery($warehouseId, $productId, $variantId);

        if ($from = $request->date('from')) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->where('occurred_at', '<=', $to->endOfDay());
        }

        $rows = ['Fecha,Tipo,Direccion,Cantidad,Costo unitario,Existencia anterior,Existencia resultante,Motivo'];

        $query->with('performedByUser:id,name')->chunk(500, function ($movements) use (&$rows) {
            foreach ($movements as $movement) {
                $rows[] = implode(',', [
                    $movement->occurred_at->toDateTimeString(),
                    $movement->movement_type->label(),
                    $movement->direction->label(),
                    $movement->quantity,
                    $movement->unit_cost,
                    $movement->previous_stock,
                    $movement->resulting_stock,
                    '"'.str_replace('"', '""', (string) $movement->reason).'"',
                ]);
            }
        });

        return response(implode("\n", $rows), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kardex.csv"',
        ]);
    }
}
