<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ClockIcon, PercentIcon } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import CartPanel from '@/components/pos/CartPanel.vue';
import CheckoutDialog from '@/components/pos/CheckoutDialog.vue';
import CustomerPickerDialog from '@/components/pos/CustomerPickerDialog.vue';
import ProductGrid from '@/components/pos/ProductGrid.vue';
import SuspendedSalesDialog from '@/components/pos/SuspendedSalesDialog.vue';
import VariantPickerDialog from '@/components/pos/VariantPickerDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { firstErrorMessage } from '@/lib/apiErrors';
import { index as posIndex } from '@/routes/pos';
import sales from '@/routes/sales';
import { useCartStore } from '@/stores/cart';
import type { CartProduct } from '@/stores/cart';
import type {
    CashSession,
    Category,
    Customer,
    PaymentMethod,
    Sale,
} from '@/types';

const props = defineProps<{
    session: CashSession | null;
    warehouseId: number | null;
    defaultCustomer: Customer | null;
    categoryOptions: Category[];
    paymentMethodOptions: PaymentMethod[];
    favoriteProducts: CartProduct[];
    maxDiscountPercentage: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Punto de venta', href: posIndex() }],
    },
});

const cart = useCartStore();
const page = usePage();
const grid = ref<InstanceType<typeof ProductGrid> | null>(null);
const checkoutOpen = ref(false);
const customerPickerOpen = ref(false);
const suspendedOpen = ref(false);
const variantPickerOpen = ref(false);
const generalDiscountOpen = ref(false);
const pendingProduct = ref<CartProduct | null>(null);
const suspending = ref(false);
const discountType = ref<'percentage' | 'fixed'>('percentage');
const discountValue = ref('');

onMounted(() => {
    cart.restore(page.props.activeCompany?.id ?? null);

    if (!cart.customer && props.defaultCustomer) {
        cart.customer = props.defaultCustomer;
    }

    window.addEventListener('keydown', onKeydown);
    grid.value?.focusBarcode();
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
});

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'F2') {
        event.preventDefault();
        grid.value?.focusSearch();
    } else if (event.key === 'F4') {
        event.preventDefault();
        customerPickerOpen.value = true;
    } else if (event.key === 'F6') {
        event.preventDefault();
        generalDiscountOpen.value = true;
    } else if (event.key === 'F8') {
        event.preventDefault();

        if (cart.lines.length) {
            suspendSale();
        }
    } else if (event.key === 'F9') {
        event.preventDefault();

        if (cart.lines.length) {
            checkoutOpen.value = true;
        }
    } else if (event.key === 'Escape') {
        checkoutOpen.value = false;
        customerPickerOpen.value = false;
        suspendedOpen.value = false;
        variantPickerOpen.value = false;
        generalDiscountOpen.value = false;
    } else if (event.ctrlKey && event.key === 'Delete') {
        event.preventDefault();
        requestClear();
    }
}

function selectProduct(product: CartProduct) {
    if (product.variants?.length && !product.matched_variant_id) {
        pendingProduct.value = product;
        variantPickerOpen.value = true;

        return;
    }

    const result = cart.addProduct(product);

    if (!result.ok && result.message) {
        toast.error(result.message);
    }

    grid.value?.focusBarcode();
}

function selectVariant(variantId: number) {
    if (!pendingProduct.value) {
        return;
    }

    const result = cart.addProduct({
        ...pendingProduct.value,
        matched_variant_id: variantId,
    });

    if (!result.ok && result.message) {
        toast.error(result.message);
    }

    pendingProduct.value = null;
}

function onBarcodeNotFound(code: string) {
    toast.error(`No se encontró ningún producto con el código «${code}».`);
}

function requestClear() {
    if (!cart.lines.length) {
        return;
    }

    if (
        window.confirm(
            '¿Limpiar toda la venta actual? Esta acción no se puede deshacer.',
        )
    ) {
        cart.clear();
    }
}

function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

async function suspendSale() {
    if (suspending.value) {
        return;
    }

    suspending.value = true;

    try {
        const response = await fetch(sales.suspend.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                register_id: props.session?.register_id,
                warehouse_id: props.warehouseId,
                cash_session_id: props.session?.id ?? null,
                customer_id: cart.customer?.id,
                notes: cart.notes || null,
                items: cart.lines.map((line) => ({
                    product_id: line.product_id,
                    product_variant_id: line.product_variant_id,
                    quantity: line.quantity,
                    discount_type: line.discount_type,
                    discount_value: line.discount_value,
                    notes: line.notes,
                })),
            }),
        });

        if (!response.ok) {
            const error = await response.json().catch(() => null);
            toast.error(
                firstErrorMessage(error, 'No se pudo suspender la venta.'),
            );

            return;
        }

        toast.success('Venta suspendida correctamente.');
        cart.clear();
        cart.clearPersisted();
    } finally {
        suspending.value = false;
    }
}

function onResumed(sale: Sale) {
    cart.clear();
    cart.customer = sale.customer_id
        ? ({ id: sale.customer_id, name: sale.customer_name } as Customer)
        : null;
    cart.loadFromSuspended(
        (sale.items ?? []).map((item) => ({
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            sku: item.sku,
            product_name: item.product_name,
            quantity: item.quantity,
            unit_price: item.unit_price,
        })),
    );
    toast.success(
        'Venta recuperada. Revisa precios y existencias antes de cobrar.',
    );
}

function onCompleted(sale: Sale) {
    cart.clear();
    cart.clearPersisted();
    window.open(sales.ticket.url(sale.id), '_blank');
}

function applyGeneralDiscount() {
    cart.generalDiscount = discountValue.value
        ? { type: discountType.value, value: discountValue.value }
        : null;
    generalDiscountOpen.value = false;
}
</script>

<template>
    <Head title="Punto de venta" />

    <div
        class="grid h-[calc(100vh-6rem)] grid-cols-1 gap-4 md:h-[calc(100vh-7rem)] lg:grid-cols-[1fr_380px]"
    >
        <div class="flex flex-col gap-3 overflow-hidden rounded-xl border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold">Punto de venta</h1>
                    <p class="text-xs text-muted-foreground">
                        Busca o escanea productos, arma el carrito y cobra.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="suspendedOpen = true"
                    >
                        <ClockIcon class="size-4" /> Ventas suspendidas
                    </Button>
                </div>
            </div>
            <ProductGrid
                ref="grid"
                :category-options="categoryOptions"
                :favorite-products="favoriteProducts"
                :warehouse-id="warehouseId ?? 0"
                @select="selectProduct"
                @barcode-not-found="onBarcodeNotFound"
            />
        </div>

        <div class="overflow-hidden rounded-xl border p-4">
            <CartPanel
                :session="session"
                :processing="suspending"
                @checkout="checkoutOpen = true"
                @suspend="suspendSale"
                @clear-requested="requestClear"
                @change-customer="customerPickerOpen = true"
                @edit-general-discount="generalDiscountOpen = true"
            />
        </div>
    </div>

    <CheckoutDialog
        v-if="session"
        v-model:open="checkoutOpen"
        :payment-method-options="paymentMethodOptions"
        :register-id="session.register_id"
        :warehouse-id="warehouseId ?? 0"
        :cash-session-id="session.id"
        @completed="onCompleted"
    />

    <CustomerPickerDialog
        v-model:open="customerPickerOpen"
        @select="(c) => (cart.customer = c)"
    />

    <SuspendedSalesDialog
        v-model:open="suspendedOpen"
        :register-id="session?.register_id ?? null"
        @resumed="onResumed"
    />

    <VariantPickerDialog
        v-model:open="variantPickerOpen"
        :product="pendingProduct"
        @select="selectVariant"
    />

    <Dialog v-model:open="generalDiscountOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle
                    >Descuento general (máx. {{ maxDiscountPercentage }}% sin
                    autorización)</DialogTitle
                >
            </DialogHeader>
            <div class="flex items-center gap-2">
                <PercentIcon class="size-4 text-muted-foreground" />
                <select
                    v-model="discountType"
                    class="h-9 rounded-md border bg-background px-2 text-sm"
                >
                    <option value="percentage">Porcentaje</option>
                    <option value="fixed">Monto fijo</option>
                </select>
                <Input
                    v-model="discountValue"
                    type="number"
                    inputmode="decimal"
                    min="0"
                    step="1"
                    placeholder="Valor"
                />
            </div>
            <DialogFooter>
                <Button @click="applyGeneralDiscount">Aplicar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
