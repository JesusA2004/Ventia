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

export type CashHandoverStatus =
    'pending' | 'approved' | 'rejected' | 'recount_requested';

export type CashHandoverDenomination = {
    denomination: number;
    quantity: number;
};

export type CashHandover = {
    id: number;
    cash_session_id: number;
    register_name?: string;
    opening_amount?: string;
    branch_name?: string;
    cashier_id: number;
    cashier_name?: string;
    approved_by: number | null;
    approver_name?: string | null;
    status: CashHandoverStatus;
    status_label: string;
    expected_cash: string;
    counted_cash: string;
    difference: string;
    denominations: CashHandoverDenomination[];
    cashier_notes: string | null;
    supervisor_notes: string | null;
    requested_at: string;
    resolved_at: string | null;
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
