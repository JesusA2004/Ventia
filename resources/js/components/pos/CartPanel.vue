<script setup lang="ts">
import {
    MinusIcon,
    PlusIcon,
    ShoppingCartIcon,
    Trash2Icon,
    UserIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import EmptyState from '@/components/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency } from '@/lib/format';
import { useCartStore } from '@/stores/cart';
import type { CashSession } from '@/types';

const props = defineProps<{
    session: CashSession | null;
    processing: boolean;
}>();

const emit = defineEmits<{
    checkout: [];
    suspend: [];
    'clear-requested': [];
    'change-customer': [];
    'edit-general-discount': [];
    'edit-promotions': [];
}>();

const cart = useCartStore();
const { can } = usePermissions();

function amountFor(key: string): number {
    return cart.lineTotals.find((l) => l.key === key)?.net ?? 0;
}

function reportQuantityResult(result: { ok: boolean; message?: string }) {
    if (!result.ok && result.message) {
        toast.error(result.message);
    }
}

function increment(key: string, allowsFraction: boolean) {
    const line = cart.lines.find((l) => l.key === key);

    if (!line) {
        return;
    }

    reportQuantityResult(
        cart.updateQuantity(
            key,
            String(Number(line.quantity) + (allowsFraction ? 0.1 : 1)),
        ),
    );
}

function decrement(key: string, allowsFraction: boolean) {
    const line = cart.lines.find((l) => l.key === key);

    if (!line) {
        return;
    }

    const next = Number(line.quantity) - (allowsFraction ? 0.1 : 1);
    cart.updateQuantity(
        key,
        String(Math.max(allowsFraction ? 0.0001 : 1, next)),
    );
}

const canCheckout = computed(() => cart.lines.length > 0 && !props.processing);
</script>

<template>
    <div class="flex h-full flex-col gap-3">
        <div class="flex items-center justify-between rounded-lg border p-3">
            <button
                type="button"
                class="flex items-center gap-2 text-left"
                @click="emit('change-customer')"
            >
                <UserIcon class="size-4 text-muted-foreground" />
                <div>
                    <p class="text-sm font-medium">
                        {{ cart.customer?.name ?? 'Selecciona un cliente' }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Cambiar cliente (F4)
                    </p>
                </div>
            </button>
            <Badge v-if="session" variant="outline"
                >Caja: {{ session.register_name }}</Badge
            >
        </div>

        <div class="flex-1 overflow-y-auto rounded-lg border">
            <EmptyState
                v-if="!cart.lines.length"
                :icon="ShoppingCartIcon"
                title="Carrito vacío"
                description="Busca o escanea un producto para agregarlo a la venta."
            />

            <ul v-else class="divide-y">
                <li v-for="line in cart.lines" :key="line.key" class="p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ line.name }}
                            </p>
                            <p class="font-mono text-xs text-muted-foreground">
                                {{ line.sku }}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon"
                            @click="cart.removeLine(line.key)"
                        >
                            <Trash2Icon class="size-4" />
                        </Button>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="icon"
                                class="size-7"
                                @click="
                                    decrement(line.key, line.allows_fraction)
                                "
                            >
                                <MinusIcon class="size-3" />
                            </Button>
                            <Input
                                :model-value="line.quantity"
                                type="number"
                                :step="line.allows_fraction ? '0.0001' : '1'"
                                min="0.0001"
                                class="h-7 w-20 text-center"
                                @update:model-value="
                                    (v) =>
                                        reportQuantityResult(
                                            cart.updateQuantity(
                                                line.key,
                                                String(v),
                                            ),
                                        )
                                "
                            />
                            <Button
                                variant="outline"
                                size="icon"
                                class="size-7"
                                @click="
                                    increment(line.key, line.allows_fraction)
                                "
                            >
                                <PlusIcon class="size-3" />
                            </Button>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold">
                                {{ formatCurrency(amountFor(line.key)) }}
                            </p>
                            <p
                                v-if="line.discount_value"
                                class="text-xs text-muted-foreground"
                            >
                                con descuento
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="can('discounts.apply')"
                        class="mt-2 flex items-center gap-2"
                    >
                        <select
                            :value="line.discount_type ?? ''"
                            class="h-7 rounded-md border bg-background px-1 text-xs"
                            @change="
                                (e) =>
                                    cart.setLineDiscount(
                                        line.key,
                                        ((e.target as HTMLSelectElement)
                                            .value as 'percentage' | 'fixed') ||
                                            null,
                                        line.discount_value,
                                    )
                            "
                        >
                            <option value="">Sin descuento</option>
                            <option value="percentage">%</option>
                            <option value="fixed">$</option>
                        </select>
                        <Input
                            v-if="line.discount_type"
                            :model-value="line.discount_value ?? ''"
                            type="number"
                            inputmode="decimal"
                            min="0"
                            step="1"
                            class="h-7 w-20 text-xs"
                            @update:model-value="
                                (v) =>
                                    cart.setLineDiscount(
                                        line.key,
                                        line.discount_type,
                                        String(v),
                                    )
                            "
                        />
                    </div>
                </li>
            </ul>
        </div>

        <div class="space-y-1 rounded-lg border p-3 text-sm">
            <div class="flex justify-between">
                <span class="text-muted-foreground">Subtotal</span>
                <span>{{ formatCurrency(cart.estimatedSubtotal) }}</span>
            </div>
            <button
                v-if="can('discounts.apply')"
                type="button"
                class="flex w-full justify-between text-muted-foreground hover:text-foreground"
                @click="emit('edit-general-discount')"
            >
                <span>Descuento general</span>
                <span
                    >-{{ formatCurrency(cart.estimatedGeneralDiscount) }}</span
                >
            </button>
            <button
                type="button"
                class="flex w-full justify-between text-muted-foreground hover:text-foreground"
                @click="emit('edit-promotions')"
            >
                <span>Promociones / cupón</span>
                <span v-if="cart.estimatedRuleDiscount > 0"
                    >-{{ formatCurrency(cart.estimatedRuleDiscount) }}</span
                >
                <span v-else>—</span>
            </button>
            <div
                v-if="cart.eligibility?.promotion"
                class="flex justify-between pl-2 text-xs text-muted-foreground"
            >
                <span>Promoción: {{ cart.eligibility.promotion.name }}</span>
                <span
                    >-{{
                        formatCurrency(
                            cart.eligibility.promotion.discount_amount,
                        )
                    }}</span
                >
            </div>
            <div
                v-if="cart.eligibility?.coupon"
                class="flex justify-between pl-2 text-xs text-muted-foreground"
            >
                <span>Cupón: {{ cart.eligibility.coupon.code }}</span>
                <span
                    >-{{
                        formatCurrency(cart.eligibility.coupon.discount_amount)
                    }}</span
                >
            </div>
            <div class="flex justify-between text-base font-bold">
                <span>Total estimado</span>
                <span>{{ formatCurrency(cart.estimatedTotal) }}</span>
            </div>
            <p class="text-xs text-muted-foreground">
                El total final se calcula en el servidor al cobrar.
            </p>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <Button
                variant="outline"
                :disabled="!cart.lines.length || processing"
                @click="emit('clear-requested')"
            >
                Limpiar
            </Button>
            <Button
                variant="outline"
                :disabled="!cart.lines.length || processing"
                @click="emit('suspend')"
            >
                Suspender (F8)
            </Button>
            <Button :disabled="!canCheckout" @click="emit('checkout')">
                Cobrar (F9)
            </Button>
        </div>
    </div>
</template>
