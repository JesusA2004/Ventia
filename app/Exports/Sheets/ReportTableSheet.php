<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\FormatsReportCells;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;

/**
 * One sheet per report table: real header row (styled, frozen, filterable),
 * money/date columns formatted as such instead of raw text, and — only for
 * whichever table the PDF/screen already treat as "the chart" for this tab
 * — a native bar chart anchored below the data, never on top of it.
 */
class ReportTableSheet implements FromArray, WithCharts, WithColumnWidths, WithEvents, WithTitle
{
    use FormatsReportCells;

    /** @var list<string> */
    private array $columns;

    /** @var list<list<mixed>> */
    private array $rows;

    private string $sheetTitle;

    public function __construct(
        private readonly array $table,
        private readonly bool $includeChart,
        string $uniqueSuffix,
        private readonly string $accentColor = '2F5FDE',
    ) {
        $this->columns = $table['columns'];
        $this->rows = $table['rows'];
        $this->sheetTitle = $this->sanitizeSheetTitle($table['title'], $uniqueSuffix);
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function columnWidths(): array
    {
        $widths = $this->computeColumnWidths($this->columns, $this->rows);
        $letters = [];

        foreach ($widths as $index => $width) {
            $letters[Coordinate::stringFromColumnIndex($index + 1)] = $width;
        }

        return $letters;
    }

    public function array(): array
    {
        $body = array_map(function (array $row) {
            foreach ($this->columns as $index => $header) {
                $kind = $this->dateKind($header);

                if ($kind !== null && isset($row[$index]) && $row[$index] !== '' && $row[$index] !== null) {
                    $excelDate = $this->toExcelDate((string) $row[$index]);

                    if ($excelDate !== null) {
                        $row[$index] = $excelDate;

                        continue;
                    }
                }

                if (is_string($row[$index] ?? null)) {
                    $normalized = str_replace(',', '', $row[$index]);

                    if ($normalized !== '' && is_numeric($normalized)) {
                        $row[$index] = (float) $normalized;
                    }
                }
            }

            return $row;
        }, $this->rows);

        return array_merge([[$this->table['title']], $this->columns], $body);
    }

    public function charts(): Chart|array
    {
        if (! $this->includeChart || count($this->rows) === 0) {
            return [];
        }

        $valueIndex = count($this->columns) - 1;
        $firstRow = 3;
        $lastRow = 2 + min(count($this->rows), 30);
        $labelColumn = 'A';
        $valueColumn = Coordinate::stringFromColumnIndex($valueIndex + 1);
        $sheet = $this->sheetTitle;

        $categories = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_STRING,
            "'{$sheet}'!\${$labelColumn}\${$firstRow}:\${$labelColumn}\${$lastRow}",
            null,
            $lastRow - $firstRow + 1,
        );
        $values = new DataSeriesValues(
            DataSeriesValues::DATASERIES_TYPE_NUMBER,
            "'{$sheet}'!\${$valueColumn}\${$firstRow}:\${$valueColumn}\${$lastRow}",
            null,
            $lastRow - $firstRow + 1,
        );

        // A DataSeries built with no plotLabel falls back to the generic
        // "Series1" in Excel's legend — give it a real name instead.
        $seriesLabel = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, [$this->columns[$valueIndex]]);
        $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0], [$seriesLabel], [$categories], [$values]);
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $chart = new Chart('chart_'.md5($sheet), new ChartTitle($this->table['title']), null, $plotArea);
        $chart->setYAxisLabel(new ChartTitle($this->columns[$valueIndex]));

        $anchorRow = $lastRow + 3;
        $chart->setTopLeftPosition('A'.$anchorRow);
        $chart->setBottomRightPosition(Coordinate::stringFromColumnIndex(min(count($this->columns), 6) + 1).($anchorRow + 16));

        return $chart;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnCount = count($this->columns);
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
                $lastDataRow = 2 + count($this->rows);

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

                $this->styleHeaderRow($sheet, 2, $columnCount, $this->accentColor);
                $sheet->setShowGridlines(false);
                $sheet->setAutoFilter("A2:{$lastColumn}2");
                $sheet->freezePane('A3');

                foreach ($this->columns as $index => $header) {
                    $column = Coordinate::stringFromColumnIndex($index + 1);
                    $kind = $this->dateKind($header);

                    if ($kind !== null) {
                        $format = $kind === 'datetime' ? 'dd/mm/yyyy hh:mm' : 'dd/mm/yyyy';
                        $sheet->getStyle("{$column}3:{$column}{$lastDataRow}")->getNumberFormat()->setFormatCode($format);

                        continue;
                    }

                    $sheet->getStyle("{$column}3:{$column}{$lastDataRow}")
                        ->getNumberFormat()->setFormatCode($this->columnNumberFormat($header));
                }

                if (count($this->rows) > 0) {
                    $this->applyZebraStripes($sheet, 3, $lastDataRow, $columnCount);
                } else {
                    $sheet->setCellValue('A3', 'Sin datos en el período seleccionado.');
                    $sheet->mergeCells("A3:{$lastColumn}3");
                    $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setARGB('FF9AA5B1');
                }
            },
        ];
    }

    private function sanitizeSheetTitle(string $title, string $suffix): string
    {
        $clean = preg_replace('/[\[\]\*\/\\\\\?:]/', '', $title) ?? $title;
        $clean = trim($clean);

        // Excel sheet names cap at 31 chars and must be unique in the
        // workbook; the suffix keeps two same-named tables (unlikely, but
        // cheap to guard) from colliding after truncation.
        $budget = 31 - (strlen($suffix) > 0 ? strlen($suffix) + 1 : 0);

        return mb_strlen($clean) > $budget
            ? rtrim(mb_substr($clean, 0, $budget)).($suffix !== '' ? " {$suffix}" : '')
            : $clean.($suffix !== '' ? " {$suffix}" : '');
    }
}
