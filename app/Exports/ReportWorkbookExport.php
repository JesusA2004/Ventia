<?php

namespace App\Exports;

use App\Exports\Sheets\ReportSummarySheet;
use App\Exports\Sheets\ReportTableSheet;
use App\Support\ReportChartTitles;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet workbook: a "Resumen" cover sheet (identity, meta, KPIs, one
 * chart) followed by one sheet per detail table. Replaces the old
 * single-sheet ReportExport, which stacked meta + KPIs + every table on top
 * of each other with one oversized chart wedged in the middle — this
 * reads as separate, exportable-on-their-own reports instead.
 *
 * @see ReportSummarySheet
 * @see ReportTableSheet
 */
class ReportWorkbookExport implements Export, WithMultipleSheets
{
    /**
     * @param  array{
     *     tab: string, reportTitle: string, companyName: string, period: string,
     *     branchName: string, generatedAt: string, generatedBy: string,
     *     primaryColor?: ?string, secondaryColor?: ?string, logoPath?: ?string, filterLabels?: list<string>,
     *     data: array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>},
     * }  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function sheets(): array
    {
        $p = $this->payload;
        $chartTable = ReportChartTitles::TABLE_BY_TAB[$p['tab']] ?? null;
        $accentColor = $this->normalizeHexColor($p['primaryColor'] ?? null) ?? '2F5FDE';

        $sheets = [
            new ReportSummarySheet([
                'reportTitle' => $p['reportTitle'],
                'companyName' => $p['companyName'],
                'period' => $p['period'],
                'branchName' => $p['branchName'],
                'filterLabels' => $p['filterLabels'] ?? [],
                'generatedAt' => $p['generatedAt'],
                'generatedBy' => $p['generatedBy'],
                'kpis' => $p['data']['kpis'],
                'accentColor' => $accentColor,
                'logoPath' => $p['logoPath'] ?? null,
            ]),
        ];

        foreach ($p['data']['tables'] as $index => $table) {
            $sheets[] = new ReportTableSheet($table, $table['title'] === $chartTable, (string) ($index + 1), $accentColor);
        }

        return $sheets;
    }

    /**
     * Company brand colors are stored as free-form "#RRGGBB" (or without the
     * hash); PhpSpreadsheet ARGB colors need a bare 6-hex-digit string.
     */
    private function normalizeHexColor(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        $hex = ltrim($color, '#');

        return preg_match('/^[0-9A-Fa-f]{6}$/', $hex) === 1 ? strtoupper($hex) : null;
    }
}
