import { describe, expect, it } from 'vitest';
import {
    formatBooleanLabel,
    formatCurrency,
    formatDate,
    formatDateTime,
    formatPercentage,
    formatQuantity,
} from './format';

describe('formatCurrency', () => {
    it('always shows exactly 2 decimals with the currency symbol', () => {
        expect(formatCurrency('20')).toBe('$20.00');
        expect(formatCurrency(19.999)).toBe('$20.00');
        expect(formatCurrency(0)).toBe('$0.00');
    });

    it('falls back to zero for null/undefined', () => {
        expect(formatCurrency(null)).toBe('$0.00');
        expect(formatCurrency(undefined)).toBe('$0.00');
    });
});

describe('formatQuantity', () => {
    it('shows a whole number when the unit does not allow fractions', () => {
        expect(formatQuantity('20.0000', false)).toBe('20');
        expect(formatQuantity(60, false)).toBe('60');
    });

    it('shows up to 2 decimals without trailing zeros when fractions are allowed', () => {
        expect(formatQuantity('9.8100', true)).toBe('9.81');
        expect(formatQuantity('9.0000', true)).toBe('9');
        expect(formatQuantity('9.5000', true)).toBe('9.5');
    });

    it('never shows more than 2 decimals even if the source has more precision', () => {
        expect(formatQuantity('9.8156', true)).toBe('9.82');
    });
});

describe('formatPercentage', () => {
    it('appends a percent sign', () => {
        expect(formatPercentage('12.5')).toBe('12.5%');
        expect(formatPercentage(10)).toBe('10%');
    });
});

describe('formatDate / formatDateTime', () => {
    it('returns an em dash for missing values', () => {
        expect(formatDate(null)).toBe('—');
        expect(formatDateTime(undefined)).toBe('—');
    });

    it('formats a valid date without throwing', () => {
        expect(formatDate('2026-07-24T10:00:00Z')).not.toBe('—');
        expect(formatDateTime('2026-07-24T10:00:00Z')).not.toBe('—');
    });
});

describe('formatBooleanLabel', () => {
    it('defaults to Sí/No', () => {
        expect(formatBooleanLabel(true)).toBe('Sí');
        expect(formatBooleanLabel(false)).toBe('No');
    });

    it('accepts custom labels', () => {
        expect(
            formatBooleanLabel(true, { true: 'Activo', false: 'Inactivo' }),
        ).toBe('Activo');
    });
});
