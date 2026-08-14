<?php

namespace App\Support;

/**
 * Every report KPI label / table column header that represents a currency
 * amount, in one place — the screen (Vue), PDF and Excel all format money
 * as money ("$1,008.60") instead of a bare number by checking against this
 * same list, instead of three separate hand-maintained guesses.
 */
final class ReportMoneyFields
{
    /** @var list<string> */
    public const LABELS = [
        'Ventas', 'Ticket promedio', 'Ticket promedio por cliente', 'Descuentos', 'Devoluciones',
        'Diferencia de caja acumulada', 'Utilidad', 'Valor del inventario', 'Ingresos por productos',
        'Margen bruto', 'Ventas a clientes registrados',
        'Total', 'Esperado', 'Contado', 'Diferencia', 'Valor', 'Ingresos', 'Margen', 'Costo', 'Precio',
        'Límite de crédito', 'Saldo actual',
    ];

    public static function isMoney(string $label): bool
    {
        return in_array($label, self::LABELS, true);
    }
}
