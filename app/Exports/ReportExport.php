<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;

/**
 * Native .xlsx report: a title/meta block, the KPI summary (if any) and
 * every detail table, laid out on one sheet, plus one native Excel chart
 * (not an image) built from whichever section has plain label/number rows —
 * the KPI block for the "summary" tab, or the first detail table otherwise.
 * Row numbers are tracked while the array is built so the chart's cell
 * ranges always point at the right rows regardless of how many KPIs/tables
 * a given tab happens to have.
 */
class ReportExport implements FromArray, ShouldAutoSize, WithCharts, WithEvents, WithTitle
{
    /** @var list<list<mixed>> */
    private array $rows = [];

    /** @var list<int> rows that should render bold (titles/headers) */
    private array $boldRows = [];

    private ?int $chartLabelColumn = null;

    private ?int $chartValueColumn = null;

    private ?int $chartFirstDataRow = null;

    private ?int $chartLastDataRow = null;

    private string $chartTitle = '';

    /**
     * @param  array{
     *     reportTitle: string, companyName: string, period: string,
     *     branchName: string, generatedAt: string, generatedBy: string,
     *     data: array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>},
     * }  $payload
     */
    public function __construct(private readonly array $payload)
    {
        $this->build();
    }

    public function title(): string
    {
        return 'Reporte';
    }

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    public function charts(): Chart|array
    {
        if ($this->chartFirstDataRow === null || $this->chartLastDataRow === null) {
            return [];
        }

        $labelColumn = $this->columnLetter($this->chartLabelColumn);
        $valueColumn = $this->columnLetter($this->chartValueColumn);
        $sheet = $this->title();

        $categories = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "'{$sheet}'!\${$labelColumn}\${$this->chartFirstDataRow}:\${$labelColumn}\${$this->chartLastDataRow}",
            null,
            $this->chartLastDataRow - $this->chartFirstDataRow + 1,
        );
        $values = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            "'{$sheet}'!\${$valueColumn}\${$this->chartFirstDataRow}:\${$valueColumn}\${$this->chartLastDataRow}",
            null,
            $this->chartLastDataRow - $this->chartFirstDataRow + 1,
        );

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [],
            [$categories],
            [$values],
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chart = new Chart('reportChart', new ChartTitle($this->chartTitle), $legend, $plotArea);

        $anchorRow = max($this->chartLastDataRow + 2, 2);
        $chart->setTopLeftPosition('A'.$anchorRow);
        $chart->setBottomRightPosition($valueColumn.($anchorRow + 18));

        return $chart;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                foreach ($this->boldRows as $row) {
                    $event->sheet->getStyle("A{$row}:Z{$row}")->getFont()->setBold(true);
                }
            },
        ];
    }

    private function build(): void
    {
        $p = $this->payload;

        $this->addRow([$p['companyName']], bold: true);
        $this->addRow([$p['reportTitle']], bold: true);
        $this->addRow(['Período', $p['period']]);
        $this->addRow(['Sucursal', $p['branchName']]);
        $this->addRow(['Generado', $p['generatedAt'].' por '.$p['generatedBy']]);
        $this->addRow([]);

        $kpis = $p['data']['kpis'];

        if (count($kpis) > 0) {
            $this->addRow(['Resumen'], bold: true);
            $this->addRow(['Concepto', 'Valor'], bold: true);
            $firstRow = count($this->rows) + 1;

            foreach ($kpis as $kpi) {
                $this->addRow([$kpi['label'], $this->numeric($kpi['value'])]);
            }

            $this->setChartSource(1, 2, $firstRow, count($this->rows), 'Resumen del período');
            $this->addRow([]);
        }

        foreach ($p['data']['tables'] as $table) {
            $this->addRow([$table['title']], bold: true);
            $this->addRow($table['columns'], bold: true);
            $firstRow = count($this->rows) + 1;

            foreach ($table['rows'] as $row) {
                $this->addRow(array_map(
                    fn ($cell, $index) => $index > 0 ? $this->numeric((string) ($cell ?? '')) : $cell,
                    $row,
                    array_keys($row),
                ));
            }

            if (count($table['rows']) > 0 && $this->chartFirstDataRow === null) {
                $this->setChartSource(1, count($table['columns']), $firstRow, count($this->rows), $table['title']);
            }

            $this->addRow([]);
        }
    }

    /**
     * @param  list<mixed>  $cells
     */
    private function addRow(array $cells, bool $bold = false): void
    {
        $this->rows[] = $cells;

        if ($bold) {
            $this->boldRows[] = count($this->rows);
        }
    }

    private function setChartSource(int $labelColumn, int $valueColumn, int $firstRow, int $lastRow, string $title): void
    {
        $this->chartLabelColumn = $labelColumn;
        $this->chartValueColumn = $valueColumn;
        $this->chartFirstDataRow = $firstRow;
        $this->chartLastDataRow = $lastRow;
        $this->chartTitle = $title;
    }

    /**
     * A value already formatted for display (e.g. "1,234.50") needs to come
     * back to a plain float for the cell, or Excel treats it as text and
     * the chart series has nothing numeric to plot.
     */
    private function numeric(string $value): float|string
    {
        $normalized = str_replace(',', '', $value);

        return is_numeric($normalized) ? (float) $normalized : $value;
    }

    private function columnLetter(?int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index ?? 1);
    }
}
