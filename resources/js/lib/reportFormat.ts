/**
 * Mirrors App\Support\ReportMoneyFields::LABELS (PHP) — same list, used so
 * the Reportes screen prefixes "$" on exactly the same KPI/column labels
 * the PDF and Excel exports treat as currency. Keep both lists in sync.
 */
export const REPORT_MONEY_LABELS = [
    'Ventas',
    'Ticket promedio',
    'Ticket promedio por cliente',
    'Descuentos',
    'Devoluciones',
    'Diferencia de caja acumulada',
    'Utilidad',
    'Valor del inventario',
    'Ingresos por productos',
    'Margen bruto',
    'Ventas a clientes registrados',
    'Total',
    'Esperado',
    'Contado',
    'Diferencia',
    'Valor',
    'Ingresos',
    'Margen',
    'Costo',
    'Precio',
    'Límite de crédito',
    'Saldo actual',
];

const NUMERIC_LABELS = [
    'Cantidad',
    'Existencia',
    'Tickets',
    'Movimientos',
    'Cobros',
    'Stock mínimo',
    ...REPORT_MONEY_LABELS,
];

export function isMoneyLabel(label: string): boolean {
    return REPORT_MONEY_LABELS.includes(label);
}

export function isNumericLabel(label: string): boolean {
    return NUMERIC_LABELS.includes(label);
}

/** Renders a report cell/KPI value for display: "$1,234.50" for money, the raw value otherwise. */
export function formatReportValue(label: string, value: string | number | null): string {
    if (value === null || value === '') {
        return '—';
    }

    return isMoneyLabel(label) ? `$${value}` : String(value);
}
