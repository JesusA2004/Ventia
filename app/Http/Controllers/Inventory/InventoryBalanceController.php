<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Http\Resources\InventoryBalanceResource;
use App\Http\Resources\WarehouseResource;
use App\Models\Branch;
use App\Models\InventoryBalance;
use App\Models\Warehouse;
use App\Support\PaginatedResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryBalanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:inventory.view');
    }

    public function index(Request $request): Response
    {
        $balances = InventoryBalance::query()
            ->with(['warehouse:id,name,branch_id', 'product:id,name,sku,minimum_stock', 'variant:id,sku', 'lot:id,lot_number,expiration_date'])
            ->when($request->integer('warehouse_id'), fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->whereHas(
                'product',
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"),
            ))
            ->when($request->boolean('low_stock'), fn ($query) => $query->whereHas(
                'product',
                fn ($q) => $q->whereColumn('inventory_balances.quantity', '<=', 'products.minimum_stock'),
            ))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Inventory/Balances/Index', [
            'balances' => PaginatedResource::make($balances, InventoryBalanceResource::class),
            'filters' => $request->only('search', 'warehouse_id', 'low_stock'),
            'branchOptions' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
            'warehouseOptions' => WarehouseResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }
}
