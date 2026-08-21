import type { Status } from './models';

export type DiscountType = 'percentage' | 'fixed_amount';

export type PromotionScopeOption = { id: number; name: string };
export type PromotionScopeProduct = { id: number; name: string; sku: string };

export type Promotion = {
    id: number;
    name: string;
    description: string | null;
    type: DiscountType;
    type_label: string;
    value: string;
    starts_at: string | null;
    ends_at: string | null;
    status: Status;
    status_label: string;
    is_active_now: boolean;
    min_purchase_amount: string | null;
    min_quantity: string | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    priority: number;
    combinable: boolean;
    notes: string | null;
    times_used?: number;
    branch_ids?: number[];
    branches?: PromotionScopeOption[];
    product_ids?: number[];
    products?: PromotionScopeProduct[];
    category_ids?: number[];
    categories?: PromotionScopeOption[];
    created_at: string;
};

export type Coupon = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    type: DiscountType;
    type_label: string;
    value: string;
    starts_at: string | null;
    ends_at: string | null;
    status: Status;
    status_label: string;
    is_active_now: boolean;
    min_purchase_amount: string | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    combinable: boolean;
    notes: string | null;
    times_used?: number;
    branch_ids?: number[];
    branches?: PromotionScopeOption[];
    product_ids?: number[];
    products?: PromotionScopeProduct[];
    category_ids?: number[];
    categories?: PromotionScopeOption[];
    created_at: string;
};

/** Result shape of POST pos/promotions/preview — see PromotionEligibilityService::resolve(). */
export type PromotionEligibility = {
    cart_total: string;
    promotion: { id: number; name: string; discount_amount: string } | null;
    coupon: {
        id: number;
        code: string;
        name: string;
        discount_amount: string;
    } | null;
    coupon_error: string | null;
};
