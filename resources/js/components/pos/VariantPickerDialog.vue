<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatCurrency } from '@/lib/format';
import type { CartProduct } from '@/stores/cart';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{ product: CartProduct | null }>();

const emit = defineEmits<{ select: [variantId: number] }>();

function choose(variantId: number) {
    emit('select', variantId);
    open.value = false;
}

function isOutOfStock(stock: string): boolean {
    return !props.product?.allows_negative_stock && Number(stock) <= 0;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle
                    >Selecciona una variante de «{{
                        product?.name
                    }}»</DialogTitle
                >
            </DialogHeader>

            <ul class="max-h-96 divide-y overflow-y-auto rounded-md border">
                <li
                    v-for="variant in product?.variants ?? []"
                    :key="variant.id"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-accent disabled:opacity-50"
                        :disabled="isOutOfStock(variant.stock)"
                        @click="choose(variant.id)"
                    >
                        <span>
                            <span class="text-sm font-medium">
                                {{
                                    variant.attributes
                                        .map((a) => a.value)
                                        .join(' / ') || variant.sku
                                }}
                            </span>
                            <span
                                class="block font-mono text-xs text-muted-foreground"
                                >{{ variant.sku }}</span
                            >
                        </span>
                        <span class="text-sm">
                            {{ formatCurrency(variant.sale_price) }}
                            <span
                                v-if="isOutOfStock(variant.stock)"
                                class="block text-xs font-medium text-destructive"
                                >Sin existencias</span
                            >
                            <span
                                v-else-if="Number(variant.stock) <= 0"
                                class="block text-xs font-medium text-muted-foreground"
                                >Venta sin stock</span
                            >
                            <span
                                v-else
                                class="block text-xs text-muted-foreground"
                                >Stock: {{ variant.stock }}</span
                            >
                        </span>
                    </button>
                </li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
