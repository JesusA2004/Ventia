<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * "Resumen" tab: high-level KPIs, plus a handful of tables/charts borrowed
 * verbatim from SalesReportService (tendencia, por sucursal, por método de
 * pago, top productos) so Resumen never disagrees with the Ventas tab — one
 * source of truth, reused rather than recalculated. The same query shapes
 * the screen, CSV, PDF and Excel exports all consume via ReportController.
 */
class SummaryReportService
{
    public function __construct(private readonly SalesReportService $salesReport) {}

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, CarbonInterface $from, CarbonInterface $to, ReportFilters $filters): array
    {
        $sales = Sale::query()->accessibleBy($user)
            ->when($filters->branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('completed_at', [$from, $to])
            ->where('status', SaleStatus::Completed);

        $salesCount = (clone $sales)->count();
        $salesTotal = (float) (clone $sales)->sum('total');
        $discountTotal = (float) (clone $sales)->sum('discount_total');
        $profitTotal = (float) (clone $sales)->sum('profit_total');
        $unitsSold = (float) SaleItem::query()
            ->whereIn('sale_id', (clone $sales)->select('sales.id'))
            ->sum('quantity');

        $cancelled = Sale::query()->accessibleBy($user)
            ->when($filters->branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', SaleStatus::Cancelled)
            ->count();

        $returns = SaleReturn::query()->accessibleBy($user)
            ->when($filters->branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('processed_at', [$from, $to])
            ->sum('total_refunded');

        $cashDifference = CashSession::query()->accessibleBy($user)
            ->when($filters->branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('difference')
            ->sum('difference');

        $kpis = [
            ['label' => 'Ventas', 'value' => number_format($salesTotal, 2)],
            ['label' => 'Tickets', 'value' => (string) $salesCount],
            ['label' => 'Ticket promedio', 'value' => number_format($salesCount > 0 ? $salesTotal / $salesCount : 0, 2)],
            ['label' => 'Descuentos', 'value' => number_format($discountTotal, 2)],
            ['label' => 'Unidades vendidas', 'value' => number_format($unitsSold, 2)],
            ['label' => 'Cancelaciones', 'value' => (string) $cancelled],
            ['label' => 'Devoluciones', 'value' => number_format((float) $returns, 2)],
            ['label' => 'Diferencia de caja acumulada', 'value' => number_format((float) $cashDifference, 2)],
        ];

        if ($user->can('products.view-costs')) {
            $kpis[] = ['label' => 'Utilidad', 'value' => number_format($profitTotal, 2)];
        }

        $salesTables = $this->salesReport->build($user, $from, $to, $filters)['tables'];
        $findTable = fn (string $title) => collect($salesTables)->firstWhere('title', $title);

        $tables = array_values(array_filter([
            $findTable('Ventas por período'),
            $findTable('Ventas por sucursal'),
            $findTable('Ventas por método de pago'),
            $findTable('Productos más vendidos'),
        ]));

        return ['kpis' => $kpis, 'tables' => $tables];
    }
}
