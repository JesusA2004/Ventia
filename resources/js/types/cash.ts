export type CashSessionStatus = 'open' | 'closed' | 'force_closed';

export type CashSession = {
    id: number;
    register_id: number;
    register_name?: string;
    branch_id: number;
    user_id: number;
    user_name?: string;
    status: CashSessionStatus;
    status_label: string;
    opened_at: string;
    opening_amount: string;
    expected_cash: string | null;
    counted_cash: string | null;
    difference: string | null;
    closed_at: string | null;
    closed_by_name?: string | null;
    opening_notes: string | null;
    closing_notes: string | null;
};

export type CashMovementType =
    | 'opening'
    | 'sale'
    | 'deposit'
    | 'withdrawal'
    | 'expense'
    | 'refund'
    | 'adjustment'
    | 'closing';

export type CashMovement = {
    id: number;
    type: CashMovementType;
    type_label: string;
    is_inflow: boolean;
    amount: string;
    reason: string;
    notes: string | null;
    user_name?: string;
    occurred_at: string;
};

export type CashSessionSummary = {
    opening_amount: string;
    cash_sales: string;
    deposits: string;
    withdrawals: string;
    expenses: string;
    refunds: string;
    adjustments: string;
    expected_cash: string;
    card_total: string;
    transfer_total: string;
    other_total: string;
    grand_total: string;
};
