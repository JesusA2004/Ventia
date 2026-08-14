import type { BadgeVariants } from '@/components/ui/badge';

const DESTRUCTIVE_HINTS = [
    'deleted', 'cancelled', 'rejected', 'deactivated', 'removed',
];
const POSITIVE_HINTS = [
    'created', 'opened', 'started', 'login', 'approved', 'restored', 'completed', 'applied',
];

/** Groups the free-form audit `action` strings (created, transfer_shipped, lot_updated, ...) into a badge color, by keyword. */
export function auditActionVariant(action: string): NonNullable<BadgeVariants['variant']> {
    if (DESTRUCTIVE_HINTS.some((hint) => action.includes(hint))) {
        return 'destructive';
    }

    if (POSITIVE_HINTS.some((hint) => action.includes(hint))) {
        return 'default';
    }

    return 'secondary';
}

/** Spanish label for the raw action slug when no friendlier description is available. */
export function auditActionLabel(action: string): string {
    return action.replaceAll('_', ' ');
}
