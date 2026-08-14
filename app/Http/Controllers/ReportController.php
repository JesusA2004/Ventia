<?php

namespace App\Http\Controllers;

use App\Exports\ReportWorkbookExport;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\User;
use App\Services\ActiveCompanyContext;
use App\Services\Reports\CashReportService;
use App\Services\Reports\CustomersReportService;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\ProductsReportService;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\SummaryReportService;
use App\Support\ReportChartTitles;
use App\Support\SvgBarChart;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reportes module: a tabbed shell (Resumen, Ventas, Inventario, Caja,
 * Productos, Clientes) sharing one set of global filters. Each tab's query
 * logic lives in its own App\Services\Reports\*ReportService class, reused
 * identically by the screen, CSV, PDF and Excel exports below — one source
 * of truth for "what does this report say", so the four never disagree.
 *
 * Rentabilidad is intentionally not a separate tab: Sale.profit_total and
 * per-item margin are already surfaced in Resumen's "Utilidad" KPI and in
 * Productos' "Margen bruto"/"Margen" column (both gated by
 * products.view-costs) — a dedicated tab would just re-slice the same
 * numbers without adding data the system doesn't already have.
 */
class ReportController extends Controller
{
    private const TABS = ['summary', 'sales', 'inventory', 'cash', 'products', 'customers'];

    private const TAB_LABELS = [
        'summary' => 'Resumen',
        'sales' => 'Ventas',
        'inventory' => 'Inventario',
        'cash' => 'Caja',
        'products' => 'Productos',
        'customers' => 'Clientes',
    ];

    public function __construct(
        private readonly ActiveCompanyContext $activeCompany,
        private readonly SummaryReportService $summaryReport,
        private readonly SalesReportService $salesReport,
        private readonly CashReportService $cashReport,
        private readonly InventoryReportService $inventoryReport,
        private readonly ProductsReportService $productsReport,
        private readonly CustomersReportService $customersReport,
    ) {}

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
            'tabs' => collect(self::TABS)->map(fn ($value) => ['value' => $value, 'label' => self::TAB_LABELS[$value]])->all(),
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'branch_id' => $branchId,
            ],
            'branchOptions' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
            'canViewProfit' => $user->can('products.view-costs'),
            'data' => $this->buildTabData($tab, $user, $from->copy()->utc(), $to->copy()->utc(), $branchId),
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
     * Professional PDF: branded header, report name, period/branch filters,
     * generation timestamp, KPI cards, a chart and detail tables. dompdf
     * can't run Chart.js (canvas-based), so the chart is rendered as static
     * SVG (see SvgBarChart) instead of a screenshot or headless-browser
     * render. Sales/Productos/Clientes tend to have the widest tables, so
     * those render landscape; the rest stay portrait.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        ['tab' => $tab, 'data' => $data, 'from' => $from, 'to' => $to, 'branchId' => $branchId] = $this->resolveTabData($request);

        $landscape = in_array($tab, ['sales', 'products', 'customers'], true);
        $company = $this->activeCompany->company();
        $logoPath = null;

        if ($company?->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            $logoPath = Storage::disk('public')->path($company->logo_path);
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'reportTitle' => 'Reporte de '.(self::TAB_LABELS[$tab] ?? $tab),
            'company' => $company,
            'logoPath' => $logoPath,
            'branchName' => $branchId ? Branch::query()->withoutGlobalScopes()->find($branchId)?->name : null,
            'filters' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()],
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $request->user()->name,
            'data' => $data,
            'chartSvg' => $this->chartSvgFor($tab, $data),
        ])->setPaper('letter', $landscape ? 'landscape' : 'portrait');

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
        $tableTitle = ReportChartTitles::TABLE_BY_TAB[$tab] ?? null;

        if ($tableTitle === null) {
            $kpis = $data['kpis'];

            if (count($kpis) === 0) {
                return '';
            }

            return SvgBarChart::render(
                array_column($kpis, 'label'),
                array_map(fn (array $kpi) => (float) str_replace(',', '', $kpi['value']), $kpis),
                'Resumen del período',
            );
        }

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
     * Native .xlsx (not a renamed CSV): see ReportWorkbookExport — a
     * multi-sheet workbook (Resumen + one sheet per detail table) built
     * from the same report data as the screen/CSV/PDF.
     */
    public function exportXlsx(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        ['tab' => $tab, 'data' => $data, 'from' => $from, 'to' => $to, 'branchId' => $branchId] = $this->resolveTabData($request);

        $export = new ReportWorkbookExport([
            'tab' => $tab,
            'reportTitle' => 'Reporte de '.(self::TAB_LABELS[$tab] ?? $tab),
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

        // from/to returned here are the company-local range (for display,
        // e.g. the PDF header) — query methods above already received the
        // UTC-converted versions, since timestamps are stored in UTC.
        return ['tab' => $tab, 'data' => $this->buildTabData($tab, $user, $from, $to, $branchId), 'from' => $localFrom, 'to' => $localTo, 'branchId' => $branchId];
    }

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    private function buildTabData(string $tab, User $user, CarbonInterface $from, CarbonInterface $to, ?int $branchId): array
    {
        return match ($tab) {
            'sales' => $this->salesReport->build($user, $from, $to, $branchId),
            'cash' => $this->cashReport->build($user, $from, $to, $branchId),
            'inventory' => $this->inventoryReport->build($user, $branchId),
            'products' => $this->productsReport->build($user, $from, $to, $branchId),
            'customers' => $this->customersReport->build($user, $from, $to, $branchId),
            default => $this->summaryReport->build($user, $from, $to, $branchId),
        };
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
}
