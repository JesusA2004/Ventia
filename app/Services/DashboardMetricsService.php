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
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $today = Carbon::today();

        $todaySalesQuery = Sale::query()
            ->accessibleBy($user)
            ->where('status', SaleStatus::Completed)
            ->whereDate('completed_at', $today);

        $salesCount = (clone $todaySalesQuery)->count();
        $salesTotal = (float) (clone $todaySalesQuery)->sum('total');
        $ticketAverage = $salesCount > 0 ? $salesTotal / $salesCount : 0.0;

        $paymentsToday = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereDate('sales.completed_at', $today)
            ->when(! $user->canAccessAllBranches(), fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')))
            ->select('payment_methods.type', DB::raw('SUM(sale_payments.amount) as total'))
            ->groupBy('payment_methods.type')
            ->pluck('total', 'type');

        $cashIncome = (float) ($paymentsToday[PaymentMethodType::Cash->value] ?? 0);
        $cardIncome = (float) ($paymentsToday[PaymentMethodType::CardDebit->value] ?? 0) + (float) ($paymentsToday[PaymentMethodType::CardCredit->value] ?? 0);

        $productsSold = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereDate('sales.completed_at', $today)
            ->when(! $user->canAccessAllBranches(), fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')))
            ->sum('sale_items.quantity');

        $cancelledCount = Sale::query()->accessibleBy($user)->where('status', SaleStatus::Cancelled)->whereDate('cancelled_at', $today)->count();
        $returnsCount = SaleReturn::query()->accessibleBy($user)->whereDate('processed_at', $today)->count();

        $openSession = CashSession::query()->where('user_id', $user->id)->where('status', CashSessionStatus::Open)->exists();

        $lowStockCount = InventoryBalance::query()
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->where('inventory_balances.company_id', $user->company_id)
            ->whereColumn('inventory_balances.quantity', '<=', 'products.minimum_stock')
            ->select('inventory_balances.product_id')
            ->distinct()
            ->count();

        return [
            'sales_count' => $salesCount,
            'sales_total' => number_format($salesTotal, 2, '.', ''),
            'ticket_average' => number_format($ticketAverage, 2, '.', ''),
            'cash_income' => number_format($cashIncome, 2, '.', ''),
            'card_income' => number_format($cardIncome, 2, '.', ''),
            'products_sold' => number_format($productsSold, 4, '.', ''),
            'cash_session_open' => $openSession,
            'low_stock_count' => $lowStockCount,
            'cancelled_count' => $cancelledCount,
            'returns_count' => $returnsCount,
            'sales_by_hour' => $this->salesByHour($user, $today),
            'sales_last_7_days' => $this->salesLast7Days($user),
            'payment_method_breakdown' => $this->paymentMethodBreakdown($user),
            'top_products' => $this->topProducts($user),
            'top_categories' => $this->topCategories($user),
        ];
    }

    /**
     * @return list<array{hour: int, total: string}>
     */
    private function salesByHour(User $user, Carbon $today): array
    {
        // Grouped in PHP rather than via SQL HOUR()/DATE(): those functions
        // aren't portable across the MySQL (production) and SQLite (test)
        // drivers this app runs against, and a single day's sales are few
        // enough rows to group safely in memory.
        $rows = Sale::query()
            ->accessibleBy($user)
            ->where('status', SaleStatus::Completed)
            ->whereDate('completed_at', $today)
            ->get(['completed_at', 'total']);

        $totals = array_fill(0, 24, '0.0000');
        foreach ($rows as $row) {
            $hour = (int) $row->completed_at->format('G');
            $totals[$hour] = bcadd($totals[$hour], (string) $row->total, 4);
        }

        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $result[] = ['hour' => $hour, 'total' => number_format((float) $totals[$hour], 2, '.', '')];
        }

        return $result;
    }

    /**
     * @return list<array{date: string, total: string}>
     */
    private function salesLast7Days(User $user): array
    {
        $since = Carbon::today()->subDays(6);

        $rows = Sale::query()
            ->accessibleBy($user)
            ->where('status', SaleStatus::Completed)
            ->where('completed_at', '>=', $since)
            ->get(['completed_at', 'total']);

        $totals = [];
        foreach ($rows as $row) {
            $day = $row->completed_at->toDateString();
            $totals[$day] = bcadd($totals[$day] ?? '0.0000', (string) $row->total, 4);
        }

        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $result[] = ['date' => $date, 'total' => number_format((float) ($totals[$date] ?? 0), 2, '.', '')];
        }

        return $result;
    }

    /**
     * @return list<array{method: string, total: string}>
     */
    private function paymentMethodBreakdown(User $user): array
    {
        $rows = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->where('sales.completed_at', '>=', Carbon::today()->subDays(6))
            ->when(! $user->canAccessAllBranches(), fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')))
            ->select('payment_methods.name as method', DB::raw('SUM(sale_payments.amount) as total'))
            ->groupBy('payment_methods.name')
            ->get()
            ->map(fn ($row) => ['method' => (string) $row->method, 'total' => number_format((float) $row->total, 2, '.', '')])
            ->all();

        return array_values($rows);
    }

    /**
     * @return list<array{name: string, quantity: string}>
     */
    private function topProducts(User $user): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->where('sales.completed_at', '>=', Carbon::today()->subDays(6))
            ->when(! $user->canAccessAllBranches(), fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')))
            ->select('sale_items.product_name_snapshot as name', DB::raw('SUM(sale_items.quantity) as quantity'))
            ->groupBy('sale_items.product_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'quantity' => number_format((float) $row->quantity, 2, '.', '')])
            ->all();

        return array_values($rows);
    }

    /**
     * @return list<array{name: string, total: string}>
     */
    private function topCategories(User $user): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->where('sales.completed_at', '>=', Carbon::today()->subDays(6))
            ->when(! $user->canAccessAllBranches(), fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')))
            ->select(DB::raw("COALESCE(categories.name, 'Sin categoría') as name"), DB::raw('SUM(sale_items.total) as total'))
            ->groupBy(DB::raw("COALESCE(categories.name, 'Sin categoría')"))
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'total' => number_format((float) $row->total, 2, '.', '')])
            ->all();

        return array_values($rows);
    }
}
