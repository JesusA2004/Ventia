<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ClockIcon, PercentIcon, TicketPercentIcon } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
import { usePermissions } from '@/composables/usePermissions';
import { firstErrorMessage } from '@/lib/apiErrors';
import { formatCurrency } from '@/lib/format';
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
const { can } = usePermissions();
const grid = ref<InstanceType<typeof ProductGrid> | null>(null);
const checkoutOpen = ref(false);
const customerPickerOpen = ref(false);
const suspendedOpen = ref(false);
const variantPickerOpen = ref(false);
const generalDiscountOpen = ref(false);
const promotionsOpen = ref(false);
const couponInput = ref('');
const pendingProduct = ref<CartProduct | null>(null);
const suspending = ref(false);
const discountType = ref<'percentage' | 'fixed'>('percentage');
const discountValue = ref('');

const branchId = computed(() => props.session?.branch_id ?? null);

onMounted(() => {
    cart.restore(page.props.activeCompany?.id ?? null);

    if (!cart.customer && props.defaultCustomer) {
        cart.customer = props.defaultCustomer;
    }

    window.addEventListener('keydown', onKeydown);
    grid.value?.focusBarcode();
});

watchDebounced(
    [() => cart.lines, () => cart.customer, () => cart.couponCode],
    () => cart.refreshEligibility(branchId.value),
    { debounce: 400, deep: true },
);

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
    } else if (event.key === 'F7') {
        event.preventDefault();
        promotionsOpen.value = true;
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
        promotionsOpen.value = false;
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

function openPromotions() {
    couponInput.value = cart.couponCode;
    promotionsOpen.value = true;
}

function applyCoupon() {
    cart.couponCode = couponInput.value.trim();
}

function removeCoupon() {
    couponInput.value = '';
    cart.couponCode = '';
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
                @edit-promotions="openPromotions"
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

    <Dialog v-model:open="promotionsOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Promociones y cupones</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <p class="mb-1.5 text-sm font-medium">
                        Promociones disponibles
                    </p>
                    <div
                        v-if="cart.eligibility?.promotion"
                        class="flex items-center justify-between rounded-lg border bg-muted/40 p-2.5 text-sm"
                    >
                        <span
                            ><TicketPercentIcon
                                class="mr-1.5 inline size-4 text-muted-foreground"
                            />{{ cart.eligibility.promotion.name }}</span
                        >
                        <span class="font-medium"
                            >-{{
                                formatCurrency(
                                    cart.eligibility.promotion.discount_amount,
                                )
                            }}</span
                        >
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Ninguna promoción aplica al carrito actual.
                    </p>
                </div>

                <div v-if="can('discounts.apply')">
                    <p class="mb-1.5 text-sm font-medium">Cupón</p>
                    <div class="flex gap-2">
                        <Input
                            v-model="couponInput"
                            placeholder="Escribir código"
                            class="uppercase"
                            @keydown.enter.prevent="applyCoupon"
                        />
                        <Button
                            v-if="cart.couponCode"
                            type="button"
                            variant="outline"
                            @click="removeCoupon"
                        >
                            Quitar
                        </Button>
                        <Button type="button" @click="applyCoupon">
                            Aplicar
                        </Button>
                    </div>
                    <p
                        v-if="cart.eligibilityLoading"
                        class="mt-1.5 text-xs text-muted-foreground"
                    >
                        Validando...
                    </p>
                    <p
                        v-else-if="cart.eligibility?.coupon_error"
                        class="mt-1.5 text-xs text-destructive"
                    >
                        {{ cart.eligibility.coupon_error }}
                    </p>
                    <div
                        v-else-if="cart.eligibility?.coupon"
                        class="mt-1.5 flex items-center justify-between rounded-lg border bg-muted/40 p-2.5 text-sm"
                    >
                        <span
                            >Cupón «{{ cart.eligibility.coupon.code }}»
                            aplicado</span
                        >
                        <span class="font-medium"
                            >-{{
                                formatCurrency(
                                    cart.eligibility.coupon.discount_amount,
                                )
                            }}</span
                        >
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button @click="promotionsOpen = false">Cerrar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
