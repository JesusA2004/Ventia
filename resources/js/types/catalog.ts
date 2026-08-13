import type { Status } from './models';

export type Category = {
    id: number;
    parent_id: number | null;
    parent_name?: string | null;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    status: Status;
    status_label: string;
    products_count?: number;
    children_count?: number;
    created_at: string;
};

export type Brand = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: Status;
    status_label: string;
    products_count?: number;
    created_at: string;
};

export type UnitType =
    'piece' | 'weight' | 'volume' | 'length' | 'package' | 'service' | 'other';

export type Unit = {
    id: number;
    company_id: number | null;
    is_global: boolean;
    name: string;
    symbol: string;
    type: UnitType;
    type_label: string;
    decimal_places: number;
    allows_fraction: boolean;
    conversion_factor: string | null;
    base_unit_id: number | null;
    status: Status;
    status_label: string;
};

export type TaxType = 'percentage' | 'fixed' | 'exempt';

export type Tax = {
    id: number;
    name: string;
    code: string;
    rate: string;
    type: TaxType;
    type_label: string;
    included_in_price: boolean;
    status: Status;
    status_label: string;
};

export type PriceList = {
    id: number;
    name: string;
    code: string;
    currency: string;
    priority: number;
    is_default: boolean;
    status: Status;
    status_label: string;
};

export type ProductAttributeValue = {
    id: number;
    attribute_id?: number;
    attribute_name?: string;
    value: string;
};

export type ProductAttribute = {
    id: number;
    name: string;
    status: Status;
    values: ProductAttributeValue[];
};

export type ProductVariant = {
    id: number;
    product_id: number;
    sku: string;
    internal_code: string | null;
    cost?: string;
    sale_price: string;
    minimum_price: string | null;
    status: Status;
    status_label: string;
    attribute_value_ids?: number[];
    attribute_values?: ProductAttributeValue[];
    label?: string;
};

export type BarcodeType =
    'EAN13' | 'EAN8' | 'UPC' | 'CODE128' | 'QR' | 'internal' | 'other';

export type ProductBarcode = {
    id: number;
    product_variant_id: number | null;
    barcode: string;
    type: BarcodeType;
    type_label: string;
    is_primary: boolean;
    quantity_multiplier: string;
};

export type ProductType = 'physical' | 'service' | 'composite';
export type TrackingType =
    | 'simple'
    | 'variants'
    | 'lot'
    | 'expiration'
    | 'lot_and_expiration'
    | 'serial'
    | 'none';

export type Product = {
    id: number;
    category_id: number | null;
    category_name?: string | null;
    brand_id: number | null;
    brand_name?: string | null;
    unit_id: number;
    unit_name?: string;
    unit_symbol?: string;
    allows_fraction?: boolean;
    tax_id: number | null;
    tax_name?: string | null;
    tax_rate?: string | null;
    name: string;
    short_name: string | null;
    slug: string;
    description: string | null;
    sku: string;
    internal_code: string | null;
    product_type: ProductType;
    product_type_label: string;
    tracking_type: TrackingType;
    tracking_type_label: string;
    image_path: string | null;
    cost?: string;
    sale_price: string;
    minimum_price: string | null;
    wholesale_price: string | null;
    minimum_stock: string;
    maximum_stock: string | null;
    allows_negative_stock: boolean;
    visible_in_pos: boolean;
    is_favorite: boolean;
    status: Status;
    status_label: string;
    variants?: ProductVariant[];
    barcodes?: ProductBarcode[];
    deleted_at: string | null;
    created_at: string;
};

export type ProductPriceHistoryEntry = {
    id: number;
    product_variant_id: number | null;
    branch_id: number | null;
    branch_name?: string | null;
    price_list_id: number | null;
    price_list_name?: string | null;
    old_price: string | null;
    new_price: string | null;
    old_cost: string | null;
    new_cost: string | null;
    percentage_change: string | null;
    reason: string | null;
    changed_by_name?: string | null;
    created_at: string;
};
