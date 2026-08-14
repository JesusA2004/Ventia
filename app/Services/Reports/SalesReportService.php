<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use App\Services\ActiveCompanyContext;
use App\Services\Reports\Concerns\BuildsReportTables;
use Carbon\CarbonInterface;

/**
 * "Ventas" tab. SalePayment/SaleItem have no company_id of their own (and
 * no BelongsToCompany global scope), so joining "sales" by table name does
 * NOT inherit Sale's automatic company scoping the way accessibleBy() does
 * on Sale itself — company_id and the branch-access restriction must be
 * applied explicitly here, or these breakdowns would leak rows across
 * tenants/branches. See ReportController's audit note for the same pattern
 * reused by ProductsReportService/CustomersReportService.
 */
class SalesReportService
{
    use BuildsReportTables;

    public function __construct(private readonly ActiveCompanyContext $activeCompany) {}

    /**
     * @param  'day'|'week'|'month'  $groupBy  Granularity of the "Ventas por período" breakdown. MariaDB/MySQL-specific DATE_FORMAT, matching this codebase's existing use of raw SQL date functions in report queries.
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, CarbonInterface $from, CarbonInterface $to, ReportFilters $filters, string $groupBy = 'day'): array
    {
        $periodExpression = match ($groupBy) {
            'week' => "DATE_FORMAT(completed_at, '%x-W%v')",
            'month' => "DATE_FORMAT(completed_at, '%Y-%m')",
            default => 'DATE(completed_at)',
        };

        $base = fn () => Sale::query()->accessibleBy($user)
            ->when($filters->branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->when($filters->registerId, fn ($q, $id) => $q->where('sales.register_id', $id))
            ->when($filters->cashierId, fn ($q, $id) => $q->where('sales.cashier_id', $id))
            ->when($filters->paymentMethodId, fn ($q, $id) => $q->whereHas('payments', fn ($p) => $p->where('payment_method_id', $id)))
            ->when($filters->productId, fn ($q, $id) => $q->whereHas('items', fn ($i) => $i->where('product_id', $id)))
            ->when($filters->categoryId, fn ($q, $id) => $q->whereHas('items.product', fn ($i) => $i->where('category_id', $id)))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed);

        $byPeriod = (clone $base())
            ->selectRaw("{$periodExpression} as period, COUNT(*) as tickets, SUM(total) as total")
            ->groupBy('period')->orderBy('period')->get();

        $byBranch = (clone $base())
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->selectRaw('branches.name as label, COUNT(*) as tickets, SUM(sales.total) as total')
            ->groupBy('branches.name')->orderByDesc('total')->get();

        $byCashier = (clone $base())
            ->join('users', 'users.id', '=', 'sales.cashier_id')
            ->selectRaw('users.name as label, COUNT(*) as tickets, SUM(sales.total) as total')
            ->groupBy('users.name')->orderByDesc('total')->get();

        $companyId = $this->activeCompany->requireCompanyId();
        $restrictToAccessibleBranches = fn ($q) => $q->when(
            ! $user->canAccessAllBranches(),
            fn ($q) => $q->whereIn('sales.branch_id', $user->branches()->pluck('branches.id')),
        );

        $byPaymentMethod = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.company_id', $companyId)
            ->tap($restrictToAccessibleBranches)
            ->when($filters->branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->when($filters->registerId, fn ($q, $id) => $q->where('sales.register_id', $id))
            ->when($filters->cashierId, fn ($q, $id) => $q->where('sales.cashier_id', $id))
            ->when($filters->paymentMethodId, fn ($q, $id) => $q->where('sale_payments.payment_method_id', $id))
            ->when($filters->productId, fn ($q, $id) => $q->whereHas('sale.items', fn ($i) => $i->where('product_id', $id)))
            ->when($filters->categoryId, fn ($q, $id) => $q->whereHas('sale.items.product', fn ($i) => $i->where('category_id', $id)))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed)
            ->selectRaw('payment_methods.name as label, COUNT(*) as tickets, SUM(sale_payments.amount) as total')
            ->groupBy('payment_methods.name')->orderByDesc('total')->get();

        $topProducts = SaleItem::query()
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
            ->where('sales.status', SaleStatus::Completed)
            ->selectRaw('sale_items.product_name_snapshot as label, SUM(sale_items.quantity) as quantity, SUM(sale_items.total) as total')
            ->groupBy('sale_items.product_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        return [
            'kpis' => [],
            'tables' => [
                $this->tableFrom('Ventas por período', ['Fecha', 'Tickets', 'Total'], $byPeriod, fn ($r) => [$r->period, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Ventas por sucursal', ['Sucursal', 'Tickets', 'Total'], $byBranch, fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Ventas por cajero', ['Cajero', 'Tickets', 'Total'], $byCashier, fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Ventas por método de pago', ['Método de pago', 'Cobros', 'Total'], $byPaymentMethod, fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Productos más vendidos', ['Producto', 'Cantidad', 'Total'], $topProducts, fn ($r) => [$r->label, (string) $r->quantity, number_format((float) $r->total, 2)]),
            ],
        ];
    }
}
