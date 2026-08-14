import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import type { Customer } from '@/types';

export type CartProduct = {
    id: number;
    name: string;
    sku: string;
    sale_price: string;
    image_path: string | null;
    unit_symbol?: string;
    allows_fraction?: boolean;
    stock: string | null;
    allows_negative_stock?: boolean;
    matched_variant_id?: number | null;
    variants: {
        id: number;
        sku: string;
        sale_price: string;
        stock: string;
        attributes: { attribute: string; value: string }[];
    }[];
};

export type CartLine = {
    key: string;
    product_id: number;
    product_variant_id: number | null;
    name: string;
    sku: string;
    quantity: string;
    unit_price: string;
    allows_fraction: boolean;
    discount_type: 'percentage' | 'fixed' | null;
    discount_value: string | null;
    notes: string | null;
    stock: string | null;
    allows_negative_stock: boolean;
};

export type CartPayment = {
    payment_method_id: number;
    payment_method_name: string;
    amount: string;
    reference?: string;
    authorization_number?: string;
    card_last_four?: string;
    bank?: string;
    terminal?: string;
};

const STORAGE_KEY = 'ventia.pos.cart';

export const useCartStore = defineStore('pos-cart', () => {
    const lines = ref<CartLine[]>([]);
    const customer = ref<Customer | null>(null);
    const notes = ref('');
    const generalDiscount = ref<{
        type: 'percentage' | 'fixed';
        value: string;
    } | null>(null);
    const payments = ref<CartPayment[]>([]);
    /**
     * The company the current cart belongs to. A superadministrator can
     * switch active company mid-session — restore() uses this to discard a
     * stale cart from a previous company instead of leaking it across the
     * tenant boundary, since this Pinia store is a client-side singleton
     * that survives Inertia navigation.
     */
    const companyId = ref<number | null>(null);

    function lineKey(productId: number, variantId: number | null): string {
        return `${productId}:${variantId ?? 'base'}`;
    }

    type QuantityResult = { ok: boolean; message?: string };

    function insufficientStockMessage(line: {
        name: string;
        sku: string;
        stock: string | null;
    }): string {
        const available = Number(line.stock ?? 0);

        return available > 0
            ? `Solo hay ${available} unidades disponibles de ${line.name} (SKU ${line.sku}).`
            : `${line.name} (SKU ${line.sku}) no tiene existencias disponibles.`;
    }

    function addProduct(product: CartProduct, quantity = '1'): QuantityResult {
        const variant = product.matched_variant_id
            ? product.variants.find((v) => v.id === product.matched_variant_id)
            : null;

        const productId = product.id;
        const variantId = variant?.id ?? null;
        const key = lineKey(productId, variantId);
        const existing = lines.value.find((l) => l.key === key);
        const stock = variant?.stock ?? product.stock;
        const allowsNegativeStock = product.allows_negative_stock ?? false;

        if (existing) {
            return updateQuantity(
                key,
                String(Number(existing.quantity) + Number(quantity)),
            );
        }

        if (!allowsNegativeStock && stock !== null && Number(stock) <= 0) {
            return {
                ok: false,
                message: insufficientStockMessage({
                    name: product.name,
                    sku: variant?.sku ?? product.sku,
                    stock,
                }),
            };
        }

        const cappedQuantity =
            !allowsNegativeStock && stock !== null && Number(quantity) > Number(stock)
                ? stock
                : quantity;

        lines.value.push({
            key,
            product_id: productId,
            product_variant_id: variantId,
            name: product.name,
            sku: variant?.sku ?? product.sku,
            quantity: cappedQuantity,
            unit_price: variant?.sale_price ?? product.sale_price,
            allows_fraction: product.allows_fraction ?? false,
            discount_type: null,
            discount_value: null,
            notes: null,
            stock,
            allows_negative_stock: allowsNegativeStock,
        });

        if (!allowsNegativeStock && stock !== null && Number(quantity) > Number(stock)) {
            return {
                ok: false,
                message: insufficientStockMessage({
                    name: product.name,
                    sku: variant?.sku ?? product.sku,
                    stock,
                }),
            };
        }

        return { ok: true };
    }

    function updateQuantity(key: string, quantity: string): QuantityResult {
        const line = lines.value.find((l) => l.key === key);

        if (!line) {
            return { ok: true };
        }

        if (
            !line.allows_negative_stock &&
            line.stock !== null &&
            Number(quantity) > Number(line.stock)
        ) {
            line.quantity = line.stock;

            return { ok: false, message: insufficientStockMessage(line) };
        }

        line.quantity = quantity;

        return { ok: true };
    }

    function setLineDiscount(
        key: string,
        type: 'percentage' | 'fixed' | null,
        value: string | null,
    ) {
        const line = lines.value.find((l) => l.key === key);

        if (line) {
            line.discount_type = type;
            line.discount_value = value;
        }
    }

    function setLineNotes(key: string, text: string) {
        const line = lines.value.find((l) => l.key === key);

        if (line) {
            line.notes = text;
        }
    }

    function removeLine(key: string) {
        lines.value = lines.value.filter((l) => l.key !== key);
    }

    function clear() {
        lines.value = [];
        customer.value = null;
        notes.value = '';
        generalDiscount.value = null;
        payments.value = [];
    }

    function loadFromSuspended(
        items: {
            product_id: number;
            product_variant_id: number | null;
            sku: string;
            product_name: string;
            quantity: string;
            unit_price: string;
        }[],
    ) {
        lines.value = items.map((item) => ({
            key: lineKey(item.product_id, item.product_variant_id),
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            name: item.product_name,
            sku: item.sku,
            quantity: item.quantity,
            unit_price: item.unit_price,
            allows_fraction: true,
            discount_type: null,
            discount_value: null,
            notes: null,
            stock: null,
            allows_negative_stock: false,
        }));
    }

    const lineTotals = computed(() =>
        lines.value.map((line) => {
            const gross = Number(line.quantity) * Number(line.unit_price);
            let discount = 0;

            if (line.discount_type === 'percentage' && line.discount_value) {
                discount = gross * (Number(line.discount_value) / 100);
            } else if (line.discount_type === 'fixed' && line.discount_value) {
                discount = Math.min(Number(line.discount_value), gross);
            }

            return { key: line.key, gross, discount, net: gross - discount };
        }),
    );

    // Client-side estimate only — the backend recalculates everything from
    // scratch at checkout and never trusts these numbers.
    const estimatedSubtotal = computed(() =>
        lineTotals.value.reduce((sum, l) => sum + l.net, 0),
    );

    const estimatedGeneralDiscount = computed(() => {
        if (!generalDiscount.value) {
            return 0;
        }

        const base = estimatedSubtotal.value;

        return generalDiscount.value.type === 'percentage'
            ? base * (Number(generalDiscount.value.value) / 100)
            : Math.min(Number(generalDiscount.value.value), base);
    });

    const estimatedTotal = computed(() =>
        Math.max(0, estimatedSubtotal.value - estimatedGeneralDiscount.value),
    );

    const itemCount = computed(() =>
        lines.value.reduce((sum, l) => sum + Number(l.quantity), 0),
    );

    function addPayment(payment: CartPayment) {
        payments.value.push(payment);
    }

    function removePayment(index: number) {
        payments.value.splice(index, 1);
    }

    const paymentsTotal = computed(() =>
        payments.value.reduce((sum, p) => sum + Number(p.amount), 0),
    );
    const paymentsPending = computed(() =>
        Math.max(0, estimatedTotal.value - paymentsTotal.value),
    );
    const paymentsChange = computed(() =>
        Math.max(0, paymentsTotal.value - estimatedTotal.value),
    );

    function persist() {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    lines: lines.value,
                    customer: customer.value,
                    notes: notes.value,
                    generalDiscount: generalDiscount.value,
                    companyId: companyId.value,
                }),
            );
        } catch {
            // localStorage may be unavailable (private mode); losing the draft is acceptable.
        }
    }

    /**
     * @param currentCompanyId the company the POS screen is currently
     * operating on (from the shared `activeCompany` Inertia prop) — pass it
     * every time the POS page mounts so a cart left over from a different
     * company (superadmin switching companies) is discarded rather than
     * restored into the wrong tenant.
     */
    function restore(currentCompanyId: number | null) {
        if (companyId.value !== null && companyId.value !== currentCompanyId) {
            clear();
        }

        companyId.value = currentCompanyId;

        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return;
            }

            const data = JSON.parse(raw);

            if (data.companyId !== currentCompanyId) {
                clearPersisted();

                return;
            }

            lines.value = data.lines ?? [];
            customer.value = data.customer ?? null;
            notes.value = data.notes ?? '';
            generalDiscount.value = data.generalDiscount ?? null;
        } catch {
            // Corrupted draft: start clean rather than crash the POS.
        }
    }

    function clearPersisted() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch {
            // Nothing to clean up if storage isn't available.
        }
    }

    watch([lines, customer, notes, generalDiscount], persist, { deep: true });

    return {
        lines,
        customer,
        notes,
        generalDiscount,
        payments,
        lineTotals,
        estimatedSubtotal,
        estimatedGeneralDiscount,
        estimatedTotal,
        itemCount,
        paymentsTotal,
        paymentsPending,
        paymentsChange,
        addProduct,
        updateQuantity,
        setLineDiscount,
        setLineNotes,
        removeLine,
        clear,
        loadFromSuspended,
        addPayment,
        removePayment,
        restore,
        clearPersisted,
    };
});
