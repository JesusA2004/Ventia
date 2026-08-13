import type { Status } from './models';

export type CustomerType = 'general_public' | 'individual' | 'business';

export type Customer = {
    id: number;
    branch_id: number | null;
    customer_type: CustomerType;
    customer_type_label: string;
    name: string;
    legal_name: string | null;
    tax_id: string | null;
    phone: string | null;
    phone_country_code: string;
    email: string | null;
    address: string | null;
    price_list_id: number | null;
    credit_limit: string;
    current_balance: string;
    notes: string | null;
    status: Status;
    status_label: string;
    sales_count?: number;
    created_at: string;
};

export type PaymentMethodType =
    | 'cash'
    | 'card_debit'
    | 'card_credit'
    | 'transfer'
    | 'voucher'
    | 'customer_credit'
    | 'other';

export type PaymentMethod = {
    id: number;
    name: string;
    code: string;
    type: PaymentMethodType;
    type_label: string;
    requires_reference: boolean;
    opens_cash_drawer: boolean;
    affects_cash: boolean;
    allows_change: boolean;
    sort_order: number;
    status: Status;
    status_label: string;
};

export type SaleStatus =
    | 'draft'
    | 'suspended'
    | 'completed'
    | 'partially_returned'
    | 'returned'
    | 'cancelled';

export type SaleItem = {
    id: number;
    product_id: number;
    product_variant_id: number | null;
    product_lot_id: number | null;
    product_name: string;
    sku: string;
    barcode: string | null;
    lot_number?: string | null;
    quantity: string;
    unit_price: string;
    original_unit_price: string;
    unit_cost?: string;
    discount_amount: string;
    tax_rate: string;
    tax_amount: string;
    subtotal: string;
    total: string;
    notes: string | null;
};

export type SalePayment = {
    id: number;
    payment_method_id: number;
    payment_method_name?: string;
    amount: string;
    reference: string | null;
    authorization_number: string | null;
    card_last_four: string | null;
    bank: string | null;
    terminal: string | null;
    status: string;
    paid_at: string;
};

export type Sale = {
    id: number;
    folio: string;
    status: SaleStatus;
    status_label: string;
    branch_id: number;
    warehouse_id: number;
    register_id: number;
    register_name?: string;
    cash_session_id: number | null;
    customer_id: number;
    customer_name?: string;
    cashier_id: number;
    cashier_name?: string;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    total: string;
    cost_total?: string;
    profit_total?: string;
    amount_received: string;
    change_amount: string;
    notes: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    items?: SaleItem[];
    payments?: SalePayment[];
    payment_method_names?: string[];
    created_at?: string;
};

export type SaleReturnItem = {
    id: number;
    sale_item_id: number;
    product_name: string;
    quantity: string;
    unit_price: string;
    total_refunded: string;
    restocked: boolean;
};

export type SaleReturn = {
    id: number;
    folio: string;
    sale_id: number;
    sale_folio?: string;
    status: string;
    total_refunded: string;
    reason: string;
    user_name?: string;
    processed_at: string;
    items?: SaleReturnItem[];
};
