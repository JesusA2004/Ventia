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
}
