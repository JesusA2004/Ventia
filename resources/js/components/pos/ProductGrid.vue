<script setup lang="ts">
import { PackageSearchIcon, SearchIcon } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency } from '@/lib/format';
import pos from '@/routes/pos';
import type { CartProduct } from '@/stores/cart';
import type { Category } from '@/types';

const props = defineProps<{
    categoryOptions: Category[];
    favoriteProducts: CartProduct[];
    warehouseId: number;
}>();

const emit = defineEmits<{
    select: [product: CartProduct];
    'barcode-not-found': [code: string];
}>();

const search = ref('');
const barcode = ref('');
const activeCategory = ref<number | null>(null);
const products = ref<CartProduct[]>([]);
const loading = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);
const barcodeInput = ref<HTMLInputElement | null>(null);
let debounceHandle: ReturnType<typeof setTimeout> | null = null;

const showingFavorites = computed(
    () => search.value.trim() === '' && activeCategory.value === null,
);
const displayedProducts = computed(() =>
    showingFavorites.value ? props.favoriteProducts : products.value,
);

async function runSearch() {
    if (showingFavorites.value) {
        products.value = [];

        return;
    }

    loading.value = true;

    try {
        const response = await fetch(
            pos.products.search.url({
                query: {
                    search: search.value,
                    category_id: activeCategory.value ?? undefined,
                    warehouse_id: props.warehouseId,
                },
            }),
            { headers: { Accept: 'application/json' } },
        );
        const payload = await response.json();
        products.value = payload.data ?? [];
    } finally {
        loading.value = false;
    }
}

watch([search, activeCategory], () => {
    if (debounceHandle) {
        clearTimeout(debounceHandle);
    }

    debounceHandle = setTimeout(runSearch, 300);
});

async function submitBarcode() {
    const code = barcode.value.trim();
    barcode.value = '';

    if (!code) {
        return;
    }

    const response = await fetch(
        pos.products.barcode.url({
            query: { barcode: code, warehouse_id: props.warehouseId },
        }),
        { headers: { Accept: 'application/json' } },
    );

    if (response.status === 404) {
        emit('barcode-not-found', code);

        return;
    }

    const payload = await response.json();
    emit('select', payload.data);
}

/**
 * Only simple products report stock at the top level — variant-tracked
 * products have null here and are gated per-variant in VariantPickerDialog
 * instead, so a null stock must never be treated as "out of stock" here.
 */
function isOutOfStock(product: CartProduct): boolean {
    return product.stock !== null && Number(product.stock) <= 0;
}

function focusBarcode() {
    nextTick(() => barcodeInput.value?.focus());
}

function focusSearch() {
    nextTick(() => searchInput.value?.focus());
}

defineExpose({ focusBarcode, focusSearch });
</script>

<template>
    <div class="flex h-full flex-col gap-3">
        <div class="grid gap-2 sm:grid-cols-2">
            <div class="relative">
                <SearchIcon
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    ref="searchInput"
                    v-model="search"
                    placeholder="Buscar por nombre, SKU o código..."
                    class="pl-9"
                />
            </div>
            <Input
                ref="barcodeInput"
                v-model="barcode"
                placeholder="Escanear o capturar código de barras..."
                class="font-mono"
                @keydown.enter.prevent="submitBarcode"
            />
        </div>

        <div v-if="categoryOptions.length" class="flex flex-wrap gap-2">
            <Badge
                :variant="activeCategory === null ? 'default' : 'outline'"
                class="cursor-pointer"
                @click="activeCategory = null"
            >
                Todas
            </Badge>
            <Badge
                v-for="category in categoryOptions"
                :key="category.id"
                :variant="
                    activeCategory === category.id ? 'default' : 'outline'
                "
                class="cursor-pointer"
                @click="activeCategory = category.id"
            >
                {{ category.name }}
            </Badge>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div
                v-if="loading"
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4"
            >
                <Skeleton
                    v-for="i in 8"
                    :key="i"
                    class="h-32 w-full rounded-lg"
                />
            </div>

            <EmptyState
                v-else-if="!displayedProducts.length"
                :icon="PackageSearchIcon"
                title="Sin productos"
                :description="
                    showingFavorites
                        ? 'No hay productos favoritos configurados.'
                        : 'No se encontraron productos con ese criterio.'
                "
            />

            <div
                v-else
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4"
            >
                <button
                    v-for="product in displayedProducts"
                    :key="product.id"
                    type="button"
                    class="flex flex-col justify-between rounded-lg border p-3 text-left transition hover:border-primary hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:border-transparent disabled:hover:shadow-none"
                    :disabled="isOutOfStock(product)"
                    :aria-label="
                        isOutOfStock(product)
                            ? `${product.name} — sin existencias`
                            : product.name
                    "
                    @click="emit('select', product)"
                >
                    <div>
                        <p class="line-clamp-2 text-sm font-medium">
                            {{ product.name }}
                        </p>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            {{ product.sku }}
                        </p>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="font-semibold">{{
                            formatCurrency(product.sale_price)
                        }}</span>
                        <Badge v-if="isOutOfStock(product)" variant="destructive">
                            Sin existencias
                        </Badge>
                        <Badge
                            v-else-if="
                                product.stock !== null &&
                                Number(product.stock) <= 5
                            "
                            variant="secondary"
                        >
                            Stock bajo
                        </Badge>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
