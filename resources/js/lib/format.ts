const LOCALE = 'es-MX';

const currencyFormatters = new Map<string, Intl.NumberFormat>();

function currencyFormatter(currency: string): Intl.NumberFormat {
    let formatter = currencyFormatters.get(currency);

    if (!formatter) {
        formatter = new Intl.NumberFormat(LOCALE, {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        currencyFormatters.set(currency, formatter);
    }

    return formatter;
}

/** Always 2 decimals, currency symbol included — e.g. formatCurrency('20') -> "$20.00". */
export function formatCurrency(
    value: number | string | null | undefined,
    currency = 'MXN',
): string {
    const amount = toNumber(value);

    if (amount === null) {
        return currencyFormatter(currency).format(0);
    }

    return currencyFormatter(currency).format(amount);
}

/**
 * Whole number when the unit doesn't allow fractions (e.g. "20"), up to 2
 * decimals — without trailing zeros — when it does (e.g. "9.81", not
 * "9.8100"). Never shows more than 2 visible decimals either way.
 */
export function formatQuantity(
    value: number | string | null | undefined,
    allowsFraction = true,
): string {
    const amount = toNumber(value) ?? 0;

    return new Intl.NumberFormat(LOCALE, {
        minimumFractionDigits: 0,
        maximumFractionDigits: allowsFraction ? 2 : 0,
    }).format(amount);
}

/** e.g. formatPercentage('12.5') -> "12.5%". */
export function formatPercentage(
    value: number | string | null | undefined,
): string {
    const amount = toNumber(value) ?? 0;

    return `${new Intl.NumberFormat(LOCALE, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount)}%`;
}

/** e.g. formatDate('2026-07-24T10:00:00Z') -> "24 jul 2026". */
export function formatDate(value: string | Date | null | undefined): string {
    const date = toDate(value);

    if (date === null) {
        return '—';
    }

    return new Intl.DateTimeFormat(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

/** e.g. formatDateTime('2026-07-24T10:00:00Z') -> "24 jul 2026, 10:00 a. m.". */
export function formatDateTime(
    value: string | Date | null | undefined,
): string {
    const date = toDate(value);

    if (date === null) {
        return '—';
    }

    return new Intl.DateTimeFormat(LOCALE, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

/** e.g. formatBooleanLabel(true) -> "Sí", formatBooleanLabel(false) -> "No". */
export function formatBooleanLabel(
    value: boolean | null | undefined,
    labels: { true: string; false: string } = { true: 'Sí', false: 'No' },
): string {
    return value ? labels.true : labels.false;
}

function toNumber(value: number | string | null | undefined): number | null {
    if (value === null || value === undefined) {
        return null;
    }

    const parsed = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function toDate(value: string | Date | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}
