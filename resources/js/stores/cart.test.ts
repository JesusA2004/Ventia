import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useCartStore } from './cart';
import type { CartProduct } from './cart';

function fakeLocalStorage() {
    let store: Record<string, string> = {};

    return {
        getItem: (key: string) => store[key] ?? null,
        setItem: (key: string, value: string) => {
            store[key] = value;
        },
        removeItem: (key: string) => {
            delete store[key];
        },
        clear: () => {
            store = {};
        },
    };
}

const product: CartProduct = {
    id: 1,
    name: 'Producto de prueba',
    sku: 'SKU-1',
    sale_price: '20.00',
    image_path: null,
    stock: '10',
    variants: [],
};

beforeEach(() => {
    setActivePinia(createPinia());
    vi.stubGlobal('localStorage', fakeLocalStorage());
});

describe('cart store — company isolation on switch', () => {
    it('keeps the cart when restoring again under the same company', async () => {
        const cart = useCartStore();
        cart.restore(1);
        cart.addProduct(product);
        await vi.waitFor(() =>
            expect(localStorage.getItem('ventia.pos.cart')).not.toBeNull(),
        );

        cart.restore(1);

        expect(cart.lines).toHaveLength(1);
    });

    it('discards the in-memory cart when a different company becomes active', () => {
        const cart = useCartStore();
        cart.restore(1);
        cart.addProduct(product);
        expect(cart.lines).toHaveLength(1);

        cart.restore(2);

        expect(cart.lines).toHaveLength(0);
        expect(cart.customer).toBeNull();
    });

    it('never rehydrates a persisted cart that belonged to a different company', async () => {
        const cart = useCartStore();
        cart.restore(1);
        cart.addProduct(product);
        await vi.waitFor(() =>
            expect(localStorage.getItem('ventia.pos.cart')).not.toBeNull(),
        );

        const cart2 = useCartStore();
        cart2.restore(2);

        expect(cart2.lines).toHaveLength(0);
    });
});
