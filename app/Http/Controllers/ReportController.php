<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Exports\ReportExport;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\ActiveCompanyContext;
use App\Support\SvgBarChart;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * First functional block of the Reportes module: a tabbed shell (Resumen,
 * Ventas, Caja, Inventario) sharing one set of global filters. Productos,
 * Clientes and Rentabilidad tabs are deliberately out of scope for this
 * round — see the delivery report.
 */
class ReportController extends Controller
{
    private const TABS = ['summary', 'sales', 'cash', 'inventory'];

    public function __construct(private readonly ActiveCompanyContext $activeCompany) {}

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
                'sales' => $this->salesData($user, $from->copy()->utc(), $to->copy()->utc(), $branchId),
                'cash' => $this->cashData($user, $from->copy()->utc(), $to->copy()->utc(), $branchId),
                'inventory' => $this->inventoryData($user, $branchId),
                default => $this->summaryData($user, $from->copy()->utc(), $to->copy()->utc(), $branchId),
            },
        ]);
    }

    public function export(Request $request): HttpResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        ['tab' => $tab, 'data' => $data] = $this->resolveTabData($request);

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

    /**
     * Professional PDF: company header, report name, period/branch filters,
     * generation timestamp, KPIs, a chart and detail tables. dompdf can't
     * run Chart.js (canvas-based), so the chart is rendered as static SVG
     * (see SvgBarChart) instead of a screenshot or headless-browser render.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        ['tab' => $tab, 'data' => $data, 'from' => $from, 'to' => $to, 'branchId' => $branchId] = $this->resolveTabData($request);

        $tabLabels = [
            'summary' => 'Resumen',
            'sales' => 'Ventas',
            'cash' => 'Caja',
            'inventory' => 'Inventario',
        ];

        $pdf = Pdf::loadView('reports.pdf', [
            'reportTitle' => 'Reporte de '.($tabLabels[$tab] ?? $tab),
            'company' => $this->activeCompany->company(),
            'branchName' => $branchId ? Branch::query()->withoutGlobalScopes()->find($branchId)?->name : null,
            'filters' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()],
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $request->user()->name,
            'data' => $data,
            'chartSvg' => $this->chartSvgFor($tab, $data),
        ])->setPaper('letter');

        return $pdf->stream("reporte-{$tab}.pdf");
    }

    /**
     * Builds the same chart the "Reportes" screen shows for this tab, as
     * static SVG for the PDF (dompdf can't run Chart.js/canvas). Reuses
     * whichever KPI/table the frontend already charts, so the PDF and the
     * on-screen report never disagree about what "the chart" for a tab is.
     *
     * @param  array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}  $data
     */
    private function chartSvgFor(string $tab, array $data): string
    {
        if ($tab === 'summary') {
            $kpis = $data['kpis'];

            return SvgBarChart::render(
                array_column($kpis, 'label'),
                array_map(fn (array $kpi) => (float) str_replace(',', '', $kpi['value']), $kpis),
                'Resumen del período',
            );
        }

        $tableTitle = match ($tab) {
            'sales' => 'Ventas por período',
            'cash' => 'Movimientos de caja por tipo',
            'inventory' => 'Existencias valorizadas por almacén',
            default => null,
        };

        $table = collect($data['tables'])->firstWhere('title', $tableTitle);

        if ($table === null || count($table['rows']) === 0) {
            return '';
        }

        $valueIndex = count($table['columns']) - 1;

        return SvgBarChart::render(
            array_map(fn (array $row) => (string) $row[0], $table['rows']),
            array_map(fn (array $row) => (float) str_replace(',', '', (string) $row[$valueIndex]), $table['rows']),
            $table['title'],
        );
    }

    /**
     * Native .xlsx (not a renamed CSV): see ReportExport — same report data
     * as the screen/CSV/PDF, laid out with a KPI block, one section per
     * table and a native Excel chart built from the first numeric section.
     */
    public function exportXlsx(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        ['tab' => $tab, 'data' => $data, 'from' => $from, 'to' => $to, 'branchId' => $branchId] = $this->resolveTabData($request);

        $tabLabels = [
            'summary' => 'Resumen',
            'sales' => 'Ventas',
            'cash' => 'Caja',
            'inventory' => 'Inventario',
        ];

        $export = new ReportExport([
            'reportTitle' => 'Reporte de '.($tabLabels[$tab] ?? $tab),
            'companyName' => $this->activeCompany->company()?->name ?? 'Ventia',
            'period' => $from->toDateString().' — '.$to->toDateString(),
            'branchName' => $branchId ? (Branch::query()->withoutGlobalScopes()->find($branchId)?->name ?? '—') : 'Todas las sucursales',
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $request->user()->name,
            'data' => $data,
        ]);

        return Excel::download($export, "reporte-{$tab}.xlsx");
    }

    /**
     * @return array{tab: string, data: array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}, from: CarbonInterface, to: CarbonInterface, branchId: ?int}
     */
    private function resolveTabData(Request $request): array
    {
        $user = $request->user();
        $tab = in_array($request->string('tab')->toString(), self::TABS, true)
            ? $request->string('tab')->toString()
            : 'summary';
        [$localFrom, $localTo] = $this->resolveDateRange($request);
        $from = $localFrom->copy()->utc();
        $to = $localTo->copy()->utc();
        $branchId = $request->integer('branch_id') ?: null;

        $data = match ($tab) {
            'sales' => $this->salesData($user, $from, $to, $branchId),
            'cash' => $this->cashData($user, $from, $to, $branchId),
            'inventory' => $this->inventoryData($user, $branchId),
            default => $this->summaryData($user, $from, $to, $branchId),
        };

        // from/to returned here are the company-local range (for display,
        // e.g. the PDF header) — query methods above already received the
        // UTC-converted versions, since timestamps are stored in UTC.
        return ['tab' => $tab, 'data' => $data, 'from' => $localFrom, 'to' => $localTo, 'branchId' => $branchId];
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function resolveDateRange(Request $request): array
    {
        // Same rule as the dashboard: default/blank ranges must resolve to
        // the company's local calendar day, not the server's UTC clock.
        $activeCompany = $this->activeCompany->company();
        $timezone = $activeCompany !== null ? $activeCompany->timezone : config('app.timezone');

        $from = $request->date('date_from', null, $timezone) ?? Carbon::today($timezone)->startOfMonth();
        $to = $request->date('date_to', null, $timezone) ?? Carbon::today($timezone);

        return [$from->startOfDay(), $to->copy()->endOfDay()];
    }

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
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
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
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

        // SalePayment/SaleItem have no company_id of their own (and no
        // BelongsToCompany global scope), so joining the "sales" table by
        // name here does NOT inherit Sale's automatic company scoping the
        // way $base above does — it must be applied explicitly, together
        // with the same branch-access restriction $base gets via
        // ->accessibleBy(), or these breakdowns would leak rows across
        // tenants/branches for reports that don't pick one specific branch.
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
            ->when($branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
            ->whereBetween('sales.completed_at', [$from, $to])
            ->where('sales.status', SaleStatus::Completed)
            ->selectRaw('payment_methods.name as label, COUNT(*) as tickets, SUM(sale_payments.amount) as total')
            ->groupBy('payment_methods.name')->orderByDesc('total')->get();

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $companyId)
            ->tap($restrictToAccessibleBranches)
            ->when($branchId, fn ($q, $id) => $q->where('sales.branch_id', $id))
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

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
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
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
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
     * @param  \Closure(mixed): list<mixed>  $mapRow
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>}
     */
    private function tableFrom(string $title, array $columns, iterable $rows, \Closure $mapRow): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => array_values(collect($rows)->map($mapRow)->all()),
        ];
    }
}
