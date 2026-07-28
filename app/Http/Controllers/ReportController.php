<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * First functional block of the Reportes module: a tabbed shell (Resumen,
 * Ventas, Caja, Inventario) sharing one set of global filters. Productos,
 * Clientes, Rentabilidad and PDF export are deliberately out of scope for
 * this round — see the delivery report.
 */
class ReportController extends Controller
{
    private const TABS = ['summary', 'sales', 'cash', 'inventory'];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $user = $request->user();
        $tab = in_array($request->string('tab')->toString(), self::TABS, true)
            ? $request->string('tab')->toString()
            : 'summary';

        [$from, $to] = $this->resolveDateRange($request);
        $branchId = $request->integer('branch_id') ?: null;

        return Inertia::render('Reports/Index', [
            'tab' => $tab,
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'branch_id' => $branchId,
            ],
            'branchOptions' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
            'canViewProfit' => $user->can('products.view-costs'),
            'data' => match ($tab) {
                'sales' => $this->salesData($user, $from, $to, $branchId),
                'cash' => $this->cashData($user, $from, $to, $branchId),
                'inventory' => $this->inventoryData($user, $branchId),
                default => $this->summaryData($user, $from, $to, $branchId),
            },
        ]);
    }

    public function export(Request $request): HttpResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        $user = $request->user();
        $tab = in_array($request->string('tab')->toString(), self::TABS, true)
            ? $request->string('tab')->toString()
            : 'summary';
        [$from, $to] = $this->resolveDateRange($request);
        $branchId = $request->integer('branch_id') ?: null;

        $data = match ($tab) {
            'sales' => $this->salesData($user, $from, $to, $branchId),
            'cash' => $this->cashData($user, $from, $to, $branchId),
            'inventory' => $this->inventoryData($user, $branchId),
            default => $this->summaryData($user, $from, $to, $branchId),
        };

        $rows = ['Concepto,Valor'];

        foreach (data_get($data, 'kpis', []) as $kpi) {
            $rows[] = '"'.str_replace('"', '""', (string) $kpi['label']).'",'.$kpi['value'];
        }

        foreach (data_get($data, 'tables', []) as $table) {
            $rows[] = '';
            $rows[] = '"'.str_replace('"', '""', (string) $table['title']).'"';
            $rows[] = implode(',', array_map(fn ($c) => '"'.str_replace('"', '""', (string) $c).'"', $table['columns']));

            foreach ($table['rows'] as $row) {
                $rows[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row));
            }
        }

        return response(implode("\n", $rows), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"reporte-{$tab}.csv\"",
        ]);
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function resolveDateRange(Request $request): array
    {
        $from = $request->date('date_from') ?? Carbon::today()->startOfMonth();
        $to = $request->date('date_to') ?? Carbon::today();

        return [$from->startOfDay(), $to->copy()->endOfDay()];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryData(User $user, CarbonInterface $from, CarbonInterface $to, ?int $branchId): array
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

    /**
     * @return array<string, mixed>
     */
    private function salesData(User $user, CarbonInterface $from, CarbonInterface $to, ?int $branchId): array
    {
        $base = fn () => Sale::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed);

        $byPeriod = (clone $base())
            ->selectRaw('DATE(completed_at) as period, COUNT(*) as tickets, SUM(total) as total')
            ->groupBy('period')->orderBy('period')->get();

        $byBranch = (clone $base())
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->selectRaw('branches.name as label, COUNT(*) as tickets, SUM(sales.total) as total')
            ->groupBy('branches.name')->orderByDesc('total')->get();

        $byCashier = (clone $base())
            ->join('users', 'users.id', '=', 'sales.cashier_id')
            ->selectRaw('users.name as label, COUNT(*) as tickets, SUM(sales.total) as total')
            ->groupBy('users.name')->orderByDesc('total')->get();

        $byPaymentMethod = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->when($branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed)
            ->selectRaw('payment_methods.name as label, COUNT(*) as tickets, SUM(sale_payments.amount) as total')
            ->groupBy('payment_methods.name')->orderByDesc('total')->get();

        return [
            'kpis' => [],
            'tables' => [
                $this->tableFrom('Ventas por período', ['Fecha', 'Tickets', 'Total'], $byPeriod, fn ($r) => [$r->period, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Ventas por sucursal', ['Sucursal', 'Tickets', 'Total'], $byBranch, fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Ventas por cajero', ['Cajero', 'Tickets', 'Total'], $byCashier, fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)]),
                $this->tableFrom('Ventas por método de pago', ['Método de pago', 'Cobros', 'Total'], $byPaymentMethod, fn ($r) => [$r->label, $r->tickets, number_format((float) $r->total, 2)]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashData(User $user, CarbonInterface $from, CarbonInterface $to, ?int $branchId): array
    {
        $sessionsWithDifferences = CashSession::query()->accessibleBy($user)
            ->with(['register:id,name', 'user:id,name'])
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('difference')
            ->where('difference', '!=', 0)
            ->orderByDesc('closed_at')
            ->get();

        $movementsByType = CashMovement::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('type, COUNT(*) as movements, SUM(amount) as total')
            ->groupBy('type')->orderBy('type')->get();

        return [
            'kpis' => [],
            'tables' => [
                $this->tableFrom(
                    'Sesiones de caja con diferencias',
                    ['Caja', 'Cajero', 'Cerrada', 'Esperado', 'Contado', 'Diferencia'],
                    $sessionsWithDifferences,
                    fn ($s) => [
                        $s->register?->name, $s->user?->name, $s->closed_at?->toDateTimeString(),
                        number_format((float) $s->expected_cash, 2), number_format((float) $s->counted_cash, 2), number_format((float) $s->difference, 2),
                    ],
                ),
                $this->tableFrom(
                    'Movimientos de caja por tipo',
                    ['Tipo', 'Movimientos', 'Total'],
                    $movementsByType,
                    fn ($r) => [$r->type->label(), $r->movements, number_format((float) $r->total, 2)],
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryData(User $user, ?int $branchId): array
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

        return [
            'kpis' => [],
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
                    'Movimientos de inventario por tipo',
                    ['Tipo', 'Movimientos', 'Cantidad'],
                    $movementsByType,
                    fn ($r) => [$r->movement_type->label(), $r->movements, $r->quantity],
                ),
            ],
        ];
    }

    /**
     * @param  iterable<mixed>  $rows
     * @param  list<string>  $columns
     * @return array{title: string, columns: list<string>, rows: array<int, mixed>}
     */
    private function tableFrom(string $title, array $columns, iterable $rows, \Closure $mapRow): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => collect($rows)->map($mapRow)->values()->all(),
        ];
    }
}
