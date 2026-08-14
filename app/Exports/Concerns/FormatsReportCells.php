<?php

namespace App\Exports\Concerns;

use App\Support\ReportMoneyFields;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared cell formatting for every report sheet, so money looks like money
 * ($1,008.60, not 1008.6), dates are real Excel dates (sortable/groupable,
 * not text), and headers/columns look consistent whether they came from
 * the Resumen sheet or a detail-table sheet.
 */
trait FormatsReportCells
{
    /** @var list<string> */
    private array $integerHeaders = ['Tickets', 'Movimientos', 'Cobros', 'Clientes nuevos', 'Cancelaciones'];

    private function columnNumberFormat(string $header): string
    {
        if (ReportMoneyFields::isMoney($header)) {
            return '"$"#,##0.00';
        }

        if (in_array($header, $this->integerHeaders, true)) {
            return '#,##0';
        }

        return '#,##0.00';
    }

    /**
     * @return 'date'|'datetime'|null
     */
    private function dateKind(string $header): ?string
    {
        return match ($header) {
            'Fecha' => 'date',
            'Cerrada' => 'datetime',
            default => null,
        };
    }

    /**
     * Converts a "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS" string into a real
     * Excel date serial so the column can be formatted, sorted and grouped
     * as a date instead of sitting there as inert text.
     */
    private function toExcelDate(string $value): ?float
    {
        try {
            return ExcelDate::PHPToExcel(new \DateTimeImmutable($value));
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     * @return list<int>
     */
    private function computeColumnWidths(array $headers, array $rows): array
    {
        return array_map(function (string $header, int $index) use ($rows) {
            $longest = mb_strlen($header);

            foreach ($rows as $row) {
                $longest = max($longest, mb_strlen((string) ($row[$index] ?? '')));
            }

            return max(12, min(42, $longest + 3));
        }, $headers, array_keys($headers));
    }

    private function styleHeaderRow(Worksheet $sheet, int $row, int $columnCount, string $fillColor, string $fontColor = 'FFFFFF'): void
    {
        $range = "A{$row}:".Coordinate::stringFromColumnIndex($columnCount)."{$row}";

        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF'.$fontColor]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.$fillColor]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9E2EC']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function applyZebraStripes(Worksheet $sheet, int $firstDataRow, int $lastDataRow, int $columnCount): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
            if (($row - $firstDataRow) % 2 === 1) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF6F8FA']],
                ]);
            }
        }
    }
}
