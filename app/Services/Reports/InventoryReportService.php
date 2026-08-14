<?php

namespace App\Services\Reports;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\Reports\Concerns\BuildsReportTables;

/**
 * "Inventario" tab: a point-in-time valuation (no date range — a balance is
 * "now", not "during a period") plus movements-by-type for the selected
 * period is intentionally NOT included here to avoid implying stock moves
 * only happened in a window when the balance itself doesn't.
 */
class InventoryReportService
{
    use BuildsReportTables;

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, ?int $branchId): array
    {
        $canViewCosts = $user->can('inventory.view-costs');

        $valuedByWarehouse = InventoryBalance::query()->accessibleBy($user)
            ->join('warehouses', 'warehouses.id', '=', 'inventory_balances.warehouse_id')
            ->when($branchId, fn ($q, $id) => $q->where('inventory_balances.branch_id', $id))
            ->selectRaw('warehouses.name as label, SUM(inventory_balances.quantity) as quantity, SUM(inventory_balances.quantity * inventory_balances.average_cost) as value')
            ->groupBy('warehouses.name')->orderBy('warehouses.name')->get();

        $movementsByType = InventoryMovement::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->selectRaw('movement_type, COUNT(*) as movements, SUM(quantity) as quantity')
            ->groupBy('movement_type')->orderBy('movement_type')->get();

        $outOfStock = \App\Models\Product::query()
            ->where('status', 'active')
            ->where('product_type', '!=', 'service')
            ->whereNotExists(function ($query) use ($branchId) {
                $query->selectRaw('1')
                    ->from('inventory_balances')
                    ->whereColumn('inventory_balances.product_id', 'products.id')
                    ->where('inventory_balances.quantity', '>', 0)
                    ->when($branchId, fn ($q, $id) => $q->where('inventory_balances.branch_id', $id));
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'sku']);

        $lowStock = InventoryBalance::query()->accessibleBy($user)
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_balances.warehouse_id')
            ->when($branchId, fn ($q, $id) => $q->where('inventory_balances.branch_id', $id))
            ->whereColumn('inventory_balances.quantity', '<=', 'products.minimum_stock')
            ->where('products.minimum_stock', '>', 0)
            ->selectRaw('products.name as product_name, warehouses.name as warehouse_name, inventory_balances.quantity as quantity, products.minimum_stock as minimum_stock')
            ->orderBy('inventory_balances.quantity')
            ->limit(25)
            ->get();

        $kpis = [
            ['label' => 'Existencia total', 'value' => number_format((float) $valuedByWarehouse->sum('quantity'), 2)],
            ['label' => 'Productos bajo stock mínimo', 'value' => (string) $lowStock->count()],
            ['label' => 'Productos sin existencias', 'value' => (string) $outOfStock->count()],
        ];

        if ($canViewCosts) {
            $kpis[] = ['label' => 'Valor del inventario', 'value' => number_format((float) $valuedByWarehouse->sum('value'), 2)];
        }

        return [
            'kpis' => $kpis,
            'tables' => [
                $this->tableFrom(
                    'Existencias valorizadas por almacén',
                    $canViewCosts ? ['Almacén', 'Existencia', 'Valor'] : ['Almacén', 'Existencia'],
                    $valuedByWarehouse,
                    fn ($r) => $canViewCosts
                        ? [$r->label, $r->quantity, number_format((float) $r->value, 2)]
                        : [$r->label, $r->quantity],
                ),
                $this->tableFrom(
                    'Productos bajo stock mínimo',
                    ['Producto', 'Almacén', 'Existencia', 'Stock mínimo'],
                    $lowStock,
                    fn ($r) => [$r->product_name, $r->warehouse_name, number_format((float) $r->quantity, 2), number_format((float) $r->minimum_stock, 2)],
                ),
                $this->tableFrom(
                    'Productos sin existencias',
                    ['Producto', 'SKU'],
                    $outOfStock,
                    fn ($r) => [$r->name, $r->sku],
                ),
                $this->tableFrom(
                    'Movimientos de inventario por tipo',
                    ['Tipo', 'Movimientos', 'Cantidad'],
                    $movementsByType,
                    fn ($r) => [$r->movement_type->label(), $r->movements, $r->quantity],
                ),
            ],
        ];
    }
}
