<?php

namespace App\Support;

/**
 * This is the one table each tab treats as "the chart" — used identically
 * by the PDF's static SVG chart and the Excel workbook's native chart, so
 * screen, PDF and Excel never show a different picture of the same report.
 */
final class ReportChartTitles
{
    public const TABLE_BY_TAB = [
        'summary' => 'Ventas por período',
        'sales' => 'Ventas por período',
        'cash' => 'Movimientos de caja por tipo',
        'inventory' => 'Existencias valorizadas por almacén',
        'products' => 'Productos más vendidos',
        'customers' => 'Clientes con mayor compra',
    ];

    /**
     * PDF gets up to three charts per tab instead of one generic "Tendencia"
     * — each keyed by the exact table title it visualizes (so the chart's
     * heading always says what it shows), mapped to which of that table's
     * columns is the bar value (the bar label is always the table's first
     * column). Every entry here must reference a table title the matching
     * *ReportService actually returns; ReportController skips any entry
     * whose table/column isn't present for the current data (e.g. a cost
     * column hidden by permission) instead of erroring.
     *
     * @var array<string, array<string, string>>
     */
    public const PDF_CHARTS_BY_TAB = [
        'summary' => [
            'Ventas por período' => 'Total',
            'Ventas por sucursal' => 'Total',
            'Ventas por método de pago' => 'Total',
        ],
        'sales' => [
            'Ventas por período' => 'Total',
            'Productos más vendidos' => 'Cantidad',
            'Ventas por método de pago' => 'Total',
        ],
        'inventory' => [
            'Existencias valorizadas por almacén' => 'Existencia',
            'Productos bajo stock mínimo' => 'Existencia',
            'Movimientos de inventario por tipo' => 'Cantidad',
        ],
        'cash' => [
            'Movimientos de caja por tipo' => 'Total',
            'Sesiones de caja con diferencias' => 'Diferencia',
        ],
        'products' => [
            'Productos más vendidos' => 'Cantidad',
            'Ventas por categoría' => 'Total',
        ],
        'customers' => [
            'Clientes con mayor compra' => 'Total',
        ],
    ];
}
