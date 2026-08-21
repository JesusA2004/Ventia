import type { Status } from './models';

export type InventoryMovementType =
    | 'initial'
    | 'purchase'
    | 'sale'
    | 'sale_return'
    | 'purchase_return'
    | 'adjustment_in'
    | 'adjustment_out'
    | 'transfer_out'
    | 'transfer_in'
    | 'damaged'
    | 'expired'
    | 'theft'
    | 'internal_use'
    | 'cancellation';

export type InventoryMovementDirection = 'in' | 'out';

export type InventoryMovement = {
    id: number;
    movement_type: InventoryMovementType;
    movement_type_label: string;
    direction: InventoryMovementDirection;
    quantity: string;
    unit_cost: string;
    previous_stock: string;
    resulting_stock: string;
    reason: string | null;
    notes: string | null;
    reference_type: string | null;
    reference_id: number | null;
    performed_by_name?: string | null;
    occurred_at: string;
};

export type InventoryBalance = {
    id: number;
    warehouse_id: number;
    warehouse_name?: string;
    product_id: number;
    product_name?: string;
    product_sku?: string;
    product_variant_id: number | null;
    variant_label?: string | null;
    product_lot_id: number | null;
    lot_number?: string | null;
    expiration_date?: string | null;
    quantity: string;
    unit_symbol?: string | null;
    unit_allows_fraction?: boolean;
    average_cost?: string;
    minimum_stock?: string;
    is_low_stock?: boolean;
};

export type ProductLot = {
    id: number;
    product_id: number;
    product_name?: string;
    product_variant_id: number | null;
    lot_number: string;
    manufacture_date: string | null;
    expiration_date: string | null;
    received_at: string | null;
    cost: string;
    status: Status;
    status_label: string;
    is_expired: boolean;
};

export type StockTransferStatus =
    | 'draft'
    | 'pending'
    | 'approved'
    | 'in_transit'
    | 'received'
    | 'partially_received'
    | 'cancelled';

export type StockTransferItem = {
    id: number;
    product_id: number;
    product_name?: string;
    product_sku?: string;
    product_variant_id: number | null;
    variant_label?: string | null;
    product_lot_id: number | null;
    quantity_requested: string;
    quantity_shipped: string | null;
    quantity_received: string | null;
    unit_cost: string;
};

export type StockTransfer = {
    id: number;
    folio: string;
    status: StockTransferStatus;
    status_label: string;
    is_cancellable: boolean;
    origin_warehouse_id: number;
    origin_warehouse_name?: string;
    destination_warehouse_id: number;
    destination_warehouse_name?: string;
    requested_by_name?: string | null;
    approved_by_name?: string | null;
    shipped_by_name?: string | null;
    received_by_name?: string | null;
    notes: string | null;
    requested_at: string | null;
    approved_at: string | null;
    shipped_at: string | null;
    received_at: string | null;
    cancelled_at: string | null;
    items?: StockTransferItem[];
    created_at: string;
};

export type StockCountStatus =
    'draft' | 'counting' | 'completed' | 'applied' | 'cancelled';

export type StockCountItem = {
    id: number;
    product_id: number;
    product_name?: string;
    product_sku?: string;
    product_variant_id: number | null;
    variant_label?: string | null;
    product_lot_id: number | null;
    expected_quantity: string;
    counted_quantity: string | null;
    difference: string | null;
};

export type StockCount = {
    id: number;
    folio: string;
    status: StockCountStatus;
    status_label: string;
    warehouse_id: number;
    warehouse_name?: string;
    branch_name?: string | null;
    started_by_name?: string | null;
    completed_by_name?: string | null;
    applied_by_name?: string | null;
    notes: string | null;
    started_at: string | null;
    completed_at: string | null;
    applied_at: string | null;
    items?: StockCountItem[];
    created_at: string;
};
