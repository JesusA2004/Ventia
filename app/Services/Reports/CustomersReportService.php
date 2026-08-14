<?php

namespace App\Services\Reports;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\Reports\Concerns\BuildsReportTables;
use Carbon\CarbonInterface;

/**
 * "Clientes" tab. Customer is company-scoped via its own BelongsToCompany
 * global scope (auto-applied, no manual company_id filter needed like
 * SaleItem/SalePayment) but is NOT branch-scoped (no accessibleBy), so
 * branch restriction is applied manually here for non-superadmin users
 * without full branch access — same reasoning as the Sales/Products tabs.
 * No credit "used" ledger exists beyond Customer.current_balance, so that
 * is reported as-is rather than deriving a separate figure.
 */
class CustomersReportService
{
    use BuildsReportTables;

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, CarbonInterface $from, CarbonInterface $to, ReportFilters $filters): array
    {
        $restrictCustomersToAccessibleBranches = fn ($q) => $q->when(
            ! $user->canAccessAllBranches(),
            fn ($q) => $q->whereIn('branch_id', $user->branches()->pluck('branches.id')),
        );

        $newCustomers = Customer::query()
            ->tap($restrictCustomersToAccessibleBranches)
            ->when($filters->branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters->customerId, fn ($q, $id) => $q->where('id', $id))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $bySale = Sale::query()->accessibleBy($user)
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->when($filters->branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->when($filters->customerId, fn ($q, $id) => $q->where('sales.customer_id', $id))
            ->when($filters->registerId, fn ($q, $id) => $q->where('sales.register_id', $id))
            ->when($filters->cashierId, fn ($q, $id) => $q->where('sales.cashier_id', $id))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed)
            ->selectRaw('customers.id, customers.name as label, COUNT(*) as tickets, SUM(sales.total) as total')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total')
            ->get();

        $topCustomers = $bySale->take(15);

        $withCredit = Customer::query()
            ->tap($restrictCustomersToAccessibleBranches)
            ->when($filters->branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters->customerId, fn ($q, $id) => $q->where('id', $id))
            ->where('credit_limit', '>', 0)
            ->orderByDesc('current_balance')
            ->limit(15)
            ->get(['id', 'name', 'credit_limit', 'current_balance']);

        $activeCustomers = $bySale->count();
        $salesToCustomers = (float) $bySale->sum('total');
        $ticketsToCustomers = (int) $bySale->sum('tickets');

        return [
            'kpis' => [
                ['label' => 'Clientes nuevos', 'value' => (string) $newCustomers],
                ['label' => 'Clientes con compras en el período', 'value' => (string) $activeCustomers],
                ['label' => 'Ventas a clientes registrados', 'value' => number_format($salesToCustomers, 2)],
                ['label' => 'Ticket promedio por cliente', 'value' => number_format($ticketsToCustomers > 0 ? $salesToCustomers / $ticketsToCustomers : 0, 2)],
            ],
            'tables' => [
                $this->tableFrom(
                    'Clientes con mayor compra',
                    ['Cliente', 'Tickets', 'Total'],
                    $topCustomers,
                    fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)],
                ),
                $this->tableFrom(
                    'Crédito de clientes',
                    ['Cliente', 'Límite de crédito', 'Saldo actual'],
                    $withCredit,
                    fn ($r) => [$r->name, number_format((float) $r->credit_limit, 2), number_format((float) $r->current_balance, 2)],
                ),
            ],
        ];
    }
}
