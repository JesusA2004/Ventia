<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * "Resumen" tab: high-level KPIs only, no tables — the same query shapes
 * the screen, CSV, PDF and Excel exports all consume via ReportController.
 */
class SummaryReportService
{
    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, CarbonInterface $from, CarbonInterface $to, ?int $branchId): array
    {
        $sales = Sale::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('completed_at', [$from, $to])
            ->where('status', SaleStatus::Completed);

        $salesCount = (clone $sales)->count();
        $salesTotal = (float) (clone $sales)->sum('total');
        $discountTotal = (float) (clone $sales)->sum('discount_total');
        $profitTotal = (float) (clone $sales)->sum('profit_total');

        $cancelled = Sale::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', SaleStatus::Cancelled)
            ->count();

        $returns = SaleReturn::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('processed_at', [$from, $to])
            ->sum('total_refunded');

        $cashDifference = CashSession::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('difference')
            ->sum('difference');

        $kpis = [
            ['label' => 'Ventas', 'value' => number_format($salesTotal, 2)],
            ['label' => 'Tickets', 'value' => (string) $salesCount],
            ['label' => 'Ticket promedio', 'value' => number_format($salesCount > 0 ? $salesTotal / $salesCount : 0, 2)],
            ['label' => 'Descuentos', 'value' => number_format($discountTotal, 2)],
            ['label' => 'Cancelaciones', 'value' => (string) $cancelled],
            ['label' => 'Devoluciones', 'value' => number_format((float) $returns, 2)],
            ['label' => 'Diferencia de caja acumulada', 'value' => number_format((float) $cashDifference, 2)],
        ];

        if ($user->can('products.view-costs')) {
            $kpis[] = ['label' => 'Utilidad', 'value' => number_format($profitTotal, 2)];
        }

        return ['kpis' => $kpis, 'tables' => []];
    }
}
