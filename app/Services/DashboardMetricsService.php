<?php

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethodType;
use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\InventoryBalance;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregation for the dashboard. Numbers here feed charts and
 * summary cards only — nothing computed here is ever written back into a
 * financial record, so plain float-friendly SQL aggregates are acceptable
 * (unlike the bcmath-only rule that applies to money actually being stored).
 */
class DashboardMetricsService
{
    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        $current = $this->periodTotals($user, $from, $to, $filters);

        $previousLength = $from->diffInSeconds($to);
        $previousFrom = $from->copy()->subSeconds($previousLength + 1);
        $previousTo = $from->copy()->subSecond();
        $previous = $this->periodTotals($user, $previousFrom, $previousTo, $filters);

        $lowStockCount = InventoryBalance::query()
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->when($user->company_id, fn ($q, $id) => $q->where('inventory_balances.company_id', $id))
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('inventory_balances.branch_id', $id))
            ->whereColumn('inventory_balances.quantity', '<=', 'products.minimum_stock')
            ->select('inventory_balances.product_id')
            ->distinct()
            ->count();

        return [
            'sales_count' => $current['sales_count'],
            'sales_total' => number_format($current['sales_total'], 2, '.', ''),
            'ticket_average' => number_format($current['ticket_average'], 2, '.', ''),
            'discount_total' => number_format($current['discount_total'], 2, '.', ''),
            'profit_total' => $user->can('products.view-costs') ? number_format($current['profit_total'], 2, '.', '') : null,
            'cash_income' => number_format($current['cash_income'], 2, '.', ''),
            'card_income' => number_format($current['card_income'], 2, '.', ''),
            'products_sold' => number_format($current['products_sold'], 2, '.', ''),
            'cancelled_count' => $current['cancelled_count'],
            'returns_count' => $current['returns_count'],
            'expected_cash' => number_format($current['expected_cash'], 2, '.', ''),
            'cash_difference' => number_format($current['cash_difference'], 2, '.', ''),
            'cash_session_open' => CashSession::query()->where('user_id', $user->id)->where('status', CashSessionStatus::Open)->exists(),
            'low_stock_count' => $lowStockCount,
            'comparison' => [
                'sales_total' => $this->percentChange($previous['sales_total'], $current['sales_total']),
                'sales_count' => $this->percentChange($previous['sales_count'], $current['sales_count']),
                'ticket_average' => $this->percentChange($previous['ticket_average'], $current['ticket_average']),
            ],
            'sales_over_time' => $this->salesOverTime($user, $from, $to, $filters),
            'sales_by_branch' => $this->salesByBranch($user, $from, $to, $filters),
            'payment_method_breakdown' => $this->paymentMethodBreakdown($user, $from, $to, $filters),
            'top_products' => $this->topProducts($user, $from, $to, $filters),
            'top_categories' => $this->topCategories($user, $from, $to, $filters),
        ];
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return array<string, float|int>
     */
    private function periodTotals(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        $sales = $this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed);

        $salesCount = (clone $sales)->count();
        $salesTotal = (float) (clone $sales)->sum('sales.total');
        $discountTotal = (float) (clone $sales)->sum('sales.discount_total');
        $profitTotal = (float) (clone $sales)->sum('sales.profit_total');
        $productsSold = (float) DB::table('sale_items')
            ->joinSub($this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed), 's', 's.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.quantity');

        $payments = DB::table('sale_payments')
            ->joinSub($this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed), 's', 's.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->select('payment_methods.type', DB::raw('SUM(sale_payments.amount) as total'))
            ->groupBy('payment_methods.type')
            ->pluck('total', 'type');

        $cancelledCount = $this->salesQuery($user, $from, $to, $filters, 'cancelled_at')->where('sales.status', SaleStatus::Cancelled)->count();

        $returns = SaleReturn::query()->accessibleBy($user)
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('processed_at', [$from, $to])
            ->count();

        $sessions = CashSession::query()->accessibleBy($user)
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['register_id'], fn ($q, $id) => $q->where('register_id', $id))
            ->when($filters['cashier_id'], fn ($q, $id) => $q->where('user_id', $id))
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('expected_cash');

        return [
            'sales_count' => $salesCount,
            'sales_total' => $salesTotal,
            'ticket_average' => $salesCount > 0 ? $salesTotal / $salesCount : 0.0,
            'discount_total' => $discountTotal,
            'profit_total' => $profitTotal,
            'products_sold' => $productsSold,
            'cash_income' => (float) ($payments[PaymentMethodType::Cash->value] ?? 0),
            'card_income' => (float) ($payments[PaymentMethodType::CardDebit->value] ?? 0) + (float) ($payments[PaymentMethodType::CardCredit->value] ?? 0),
            'cancelled_count' => $cancelledCount,
            'returns_count' => $returns,
            'expected_cash' => (float) (clone $sessions)->sum('expected_cash'),
            'cash_difference' => (float) (clone $sessions)->sum('difference'),
        ];
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return Builder<Sale>
     */
    private function salesQuery(User $user, Carbon $from, Carbon $to, array $filters, string $dateColumn = 'completed_at'): Builder
    {
        return Sale::query()->accessibleBy($user)
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->when($filters['register_id'], fn ($q, $id) => $q->where('sales.register_id', $id))
            ->when($filters['cashier_id'], fn ($q, $id) => $q->where('sales.cashier_id', $id))
            ->whereBetween("sales.{$dateColumn}", [$from, $to]);
    }

    private function percentChange(float $previous, float $current): ?float
    {
        if (abs($previous) < 0.0001) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return list<array{date: string, total: string, tickets: int}>
     */
    private function salesOverTime(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        $rows = $this->salesQuery($user, $from, $to, $filters)
            ->where('sales.status', SaleStatus::Completed)
            ->get(['completed_at', 'total']);

        $days = [];
        foreach ($rows as $row) {
            $day = $row->completed_at->toDateString();
            $days[$day]['total'] = bcadd($days[$day]['total'] ?? '0.0000', (string) $row->total, 4);
            $days[$day]['tickets'] = ($days[$day]['tickets'] ?? 0) + 1;
        }

        $result = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $date = $cursor->toDateString();
            $result[] = [
                'date' => $date,
                'total' => number_format((float) ($days[$date]['total'] ?? 0), 2, '.', ''),
                'tickets' => $days[$date]['tickets'] ?? 0,
            ];
            $cursor->addDay();
        }

        return $result;
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return array<int, array{name: string, total: string}>
     */
    private function salesByBranch(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        return DB::table('branches')
            ->joinSub($this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed), 's', 's.branch_id', '=', 'branches.id')
            ->select('branches.name as name', DB::raw('SUM(s.total) as total'))
            ->groupBy('branches.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'total' => number_format((float) $row->total, 2, '.', '')])
            ->values()
            ->all();
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return array<int, array{method: string, total: string}>
     */
    private function paymentMethodBreakdown(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        return DB::table('sale_payments')
            ->joinSub($this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed), 's', 's.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->select('payment_methods.name as method', DB::raw('SUM(sale_payments.amount) as total'))
            ->groupBy('payment_methods.name')
            ->get()
            ->map(fn ($row) => ['method' => (string) $row->method, 'total' => number_format((float) $row->total, 2, '.', '')])
            ->values()
            ->all();
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return array<int, array{name: string, quantity: string}>
     */
    private function topProducts(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        return DB::table('sale_items')
            ->joinSub($this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed), 's', 's.id', '=', 'sale_items.sale_id')
            ->select('sale_items.product_name_snapshot as name', DB::raw('SUM(sale_items.quantity) as quantity'))
            ->groupBy('sale_items.product_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'quantity' => number_format((float) $row->quantity, 2, '.', '')])
            ->values()
            ->all();
    }

    /**
     * @param  array{branch_id: int|null, register_id: int|null, cashier_id: int|null}  $filters
     * @return array<int, array{name: string, total: string}>
     */
    private function topCategories(User $user, Carbon $from, Carbon $to, array $filters): array
    {
        return DB::table('sale_items')
            ->joinSub($this->salesQuery($user, $from, $to, $filters)->where('sales.status', SaleStatus::Completed), 's', 's.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select(DB::raw("COALESCE(categories.name, 'Sin categoría') as name"), DB::raw('SUM(sale_items.total) as total'))
            ->groupBy(DB::raw("COALESCE(categories.name, 'Sin categoría')"))
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'total' => number_format((float) $row->total, 2, '.', '')])
            ->values()
            ->all();
    }
}
