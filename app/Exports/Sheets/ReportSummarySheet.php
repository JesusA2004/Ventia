<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\FormatsReportCells;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * "Resumen" sheet: a cover page for the workbook — company/report identity,
 * period/branch/generation metadata, then the KPI block as a real table
 * (not free-floating text) with its own native chart underneath, sized so
 * it never overlaps the KPI rows above it.
 */
class ReportSummarySheet implements FromArray, WithCharts, WithColumnWidths, WithEvents, WithTitle
{
    use FormatsReportCells;

    private int $kpiHeaderRow = 0;

    private int $kpiFirstRow = 0;

    private int $kpiLastRow = 0;

    /**
     * @param  array{reportTitle: string, companyName: string, period: string, branchName: string, generatedAt: string, generatedBy: string, kpis: list<array{label: string, value: string}>}  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 22];
    }

    public function array(): array
    {
        $p = $this->payload;
        $rows = [
            [$p['companyName']],
            [$p['reportTitle']],
            [''], // a bare [] row is silently dropped by Maatwebsite's FromArray pipeline (ArrayHelper::ensureMultipleRows treats it as "zero rows"), so every spacer needs a non-empty cell.
            ['Período', $p['period']],
            ['Sucursal', $p['branchName']],
            ['Generado', $p['generatedAt'].' por '.$p['generatedBy']],
            [''],
        ];

        if (count($p['kpis']) > 0) {
            $rows[] = ['Resumen del período'];
            $this->kpiHeaderRow = count($rows) + 1;
            $rows[] = ['Concepto', 'Valor'];
            $this->kpiFirstRow = count($rows) + 1;

            foreach ($p['kpis'] as $kpi) {
                $normalized = str_replace(',', '', $kpi['value']);
                $rows[] = [$kpi['label'], is_numeric($normalized) ? (float) $normalized : $kpi['value']];
            }

            $this->kpiLastRow = count($rows);
        }

        return $rows;
    }

    public function charts(): Chart|array
    {
        if ($this->kpiFirstRow === 0) {
            return [];
        }

        $categories = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "'Resumen'!\$A\${$this->kpiFirstRow}:\$A\${$this->kpiLastRow}",
            null,
            $this->kpiLastRow - $this->kpiFirstRow + 1,
        );
        $values = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            "'Resumen'!\$B\${$this->kpiFirstRow}:\$B\${$this->kpiLastRow}",
            null,
            $this->kpiLastRow - $this->kpiFirstRow + 1,
        );

        $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0], [], [$categories], [$values]);
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $chart = new Chart('resumenChart', new ChartTitle('Resumen del período'), null, $plotArea);
        $chart->setYAxisLabel(new ChartTitle('Importe'));

        $anchorRow = $this->kpiLastRow + 2;
        $chart->setTopLeftPosition('D'.max($this->kpiHeaderRow, 2));
        $chart->setBottomRightPosition('L'.($anchorRow + 16));

        return $chart;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('A2:D2');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF52606D');
                $sheet->getStyle('A4:A6')->getFont()->setBold(true);
                $sheet->getStyle('A4:A6')->getFont()->getColor()->setARGB('FF52606D');

                if ($this->kpiHeaderRow > 0) {
                    $sheet->mergeCells('A'.($this->kpiHeaderRow - 1).':B'.($this->kpiHeaderRow - 1));
                    $sheet->getStyle('A'.($this->kpiHeaderRow - 1))->getFont()->setBold(true)->setSize(12);
                    $this->styleHeaderRow($sheet, $this->kpiHeaderRow, 2, '2F5FDE');
                    $sheet->getStyle("B{$this->kpiFirstRow}:B{$this->kpiLastRow}")
                        ->getNumberFormat()->setFormatCode('"$"#,##0.00');
                    $sheet->getStyle("A{$this->kpiFirstRow}:B{$this->kpiLastRow}")
                        ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $this->applyZebraStripes($sheet, $this->kpiFirstRow, $this->kpiLastRow, 2);
                }

                $sheet->getSheetView()->setZoomScale(100);
            },
        ];
    }
}
