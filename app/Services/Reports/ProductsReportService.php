<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\ActiveCompanyContext;
use App\Services\Reports\Concerns\BuildsReportTables;
use Carbon\CarbonInterface;

/**
 * "Productos" tab: performance per product for the selected period, built
 * from real sale-item data (no fabricated metrics). Margin is only shown
 * to users with `products.view-costs`, same gate the Resumen/Ventas tabs
 * already use for cost-sensitive figures. Reuses the same manual
 * company/branch scoping SalesReportService applies to SaleItem, since the
 * model itself carries neither.
 */
class ProductsReportService
{
    use BuildsReportTables;

    public function __construct(private readonly ActiveCompanyContext $activeCompany) {}

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, CarbonInterface $from, CarbonInterface $to, ReportFilters $filters): array
    {
        $canViewCosts = $user->can('products.view-costs');
        $companyId = $this->activeCompany->requireCompanyId();
        $restrictToAccessibleBranches = fn ($q) => $q->when(
            ! $user->canAccessAllBranches(),
            fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')),
        );

        $soldItems = fn () => SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $companyId)
            ->tap($restrictToAccessibleBranches)
            ->when($filters->branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->when($filters->registerId, fn ($q, $id) => $q->where('sales.register_id', $id))
            ->when($filters->cashierId, fn ($q, $id) => $q->where('sales.cashier_id', $id))
            ->when($filters->productId, fn ($q, $id) => $q->where('sale_items.product_id', $id))
            ->when($filters->categoryId, fn ($q, $id) => $q->whereHas('product', fn ($p) => $p->where('category_id', $id)))
            ->when($filters->paymentMethodId, fn ($q, $id) => $q->whereHas('sale.payments', fn ($p) => $p->where('payment_method_id', $id)))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed);

        $byProduct = $soldItems()
            ->selectRaw(
                'sale_items.product_id, sale_items.product_name_snapshot as label, '.
                'SUM(sale_items.quantity) as quantity, SUM(sale_items.total) as total, '.
                'SUM(sale_items.unit_cost * sale_items.quantity) as cost'
            )
            ->groupBy('sale_items.product_id', 'sale_items.product_name_snapshot')
            ->get();

        $topSellers = $byProduct->sortByDesc('quantity')->take(15)->values();
        $leastSellers = $byProduct->sortBy('quantity')->take(10)->values();

        $byCategory = $soldItems()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("COALESCE(categories.name, 'Sin categoría') as label, SUM(sale_items.quantity) as quantity, SUM(sale_items.total) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        $noMovement = Product::query()
            ->where('status', 'active')
            ->where('product_type', '!=', 'service')
            ->when($filters->productId, fn ($q, $id) => $q->where('id', $id))
            ->when($filters->categoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->whereNotExists(function ($query) use ($from, $to, $companyId, $filters, $user) {
                $query->selectRaw('1')
                    ->from('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->whereColumn('sale_items.product_id', 'products.id')
                    ->where('sales.company_id', $companyId)
                    ->when(! $user->canAccessAllBranches(), fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')))
                    ->when($filters->branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
                    ->whereBetween('sales.completed_at', [$from, $to])
                    ->where('sales.status', SaleStatus::Completed);
            })
            ->orderBy('name')
            ->limit(25)
            ->select('id', 'name', 'sku')
            ->get();

        $unitsSold = (float) $byProduct->sum('quantity');
        $revenue = (float) $byProduct->sum('total');
        $costTotal = (float) $byProduct->sum('cost');

        $kpis = [
            ['label' => 'Productos vendidos', 'value' => (string) $byProduct->count()],
            ['label' => 'Unidades vendidas', 'value' => number_format($unitsSold, 2)],
            ['label' => 'Ingresos por productos', 'value' => number_format($revenue, 2)],
        ];

        if ($canViewCosts) {
            $kpis[] = ['label' => 'Margen bruto', 'value' => number_format($revenue - $costTotal, 2)];
        }

        return [
            'kpis' => $kpis,
            'tables' => [
                $this->tableFrom(
                    'Productos más vendidos',
                    $canViewCosts ? ['Producto', 'Cantidad', 'Ingresos', 'Margen'] : ['Producto', 'Cantidad', 'Ingresos'],
                    $topSellers,
                    fn ($r) => $canViewCosts
                        ? [$r->label, number_format((float) $r->quantity, 2), number_format((float) $r->total, 2), number_format((float) $r->total - (float) $r->cost, 2)]
                        : [$r->label, number_format((float) $r->quantity, 2), number_format((float) $r->total, 2)],
                ),
                $this->tableFrom(
                    'Productos menos vendidos',
                    ['Producto', 'Cantidad', 'Ingresos'],
                    $leastSellers,
                    fn ($r) => [$r->label, number_format((float) $r->quantity, 2), number_format((float) $r->total, 2)],
                ),
                $this->tableFrom(
                    'Ventas por categoría',
                    ['Categoría', 'Cantidad', 'Total'],
                    $byCategory,
                    fn ($r) => [$r->label, number_format((float) $r->quantity, 2), number_format((float) $r->total, 2)],
                ),
                $this->tableFrom(
                    'Productos sin movimiento en el período',
                    ['Producto', 'SKU'],
                    $noMovement,
                    fn ($r) => [$r->name, $r->sku],
                ),
            ],
        ];
    }
}
