<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { Product, ProductVariant } from '@/types';

const props = defineProps<{
    /** Ranks products that track lots/caducidad first, without hiding the rest. */
    prioritizeLots?: boolean;
}>();

const emit = defineEmits<{
    select: [product: Product, variant: ProductVariant | null];
}>();

const open = ref(false);
const search = ref('');
const results = ref<Product[]>([]);
const loading = ref(false);
const errored = ref(false);
const expandedProduct = ref<number | null>(null);

watchDebounced(
    search,
    async (value) => {
        if (!value) {
            results.value = [];
            errored.value = false;

            return;
        }

        loading.value = true;
        errored.value = false;

        try {
            const response = await fetch(
                ProductController.search.url({
                    query: {
                        search: value,
                        ...(props.prioritizeLots
                            ? { prioritize_lots: 1 }
                            : {}),
                    },
                }),
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                results.value = [];
                errored.value = true;

                return;
            }

            const payload = await response.json();
            results.value = payload.data ?? [];
        } catch {
            results.value = [];
            errored.value = true;
        } finally {
            loading.value = false;
        }
    },
    { debounce: 300 },
);

function choose(product: Product, variant: ProductVariant | null = null) {
    if (
        !variant &&
        product.tracking_type === 'variants' &&
        (product.variants?.length ?? 0) > 0
    ) {
        expandedProduct.value = product.id;

        return;
    }

    emit('select', product, variant);
    open.value = false;
    search.value = '';
    results.value = [];
    expandedProduct.value = null;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                class="w-full justify-start"
            >
                <span class="text-muted-foreground"
                    >Buscar producto por nombre, SKU o código de barras...</span
                >
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-96 p-0" align="start">
            <div class="border-b p-2">
                <Input
                    v-model="search"
                    autofocus
                    placeholder="Nombre, SKU o código de barras"
                />
            </div>
            <div class="max-h-80 overflow-y-auto p-1">
                <p v-if="loading" class="p-3 text-sm text-muted-foreground">
                    Buscando...
                </p>
                <p
                    v-else-if="errored"
                    class="p-3 text-sm text-destructive"
                >
                    No se pudo buscar productos. Intenta de nuevo.
                </p>
                <p
                    v-else-if="search && results.length === 0"
                    class="p-3 text-sm text-muted-foreground"
                >
                    Sin resultados.
                </p>
                <div v-for="product in results" :key="product.id">
                    <button
                        type="button"
                        class="flex w-full flex-col rounded-md px-3 py-2 text-left text-sm hover:bg-accent"
                        @click="choose(product)"
                    >
                        <span class="font-medium">{{ product.name }}</span>
                        <span class="text-xs text-muted-foreground"
                            >SKU {{ product.sku }} ·
                            {{ product.sale_price }}</span
                        >
                    </button>
                    <div
                        v-if="expandedProduct === product.id"
                        class="ml-3 space-y-1 border-l pl-2"
                    >
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="flex w-full flex-col rounded-md px-3 py-1.5 text-left text-xs hover:bg-accent"
                            @click="choose(product, variant)"
                        >
                            <span>{{ variant.label || variant.sku }}</span>
                            <span class="text-muted-foreground"
                                >SKU {{ variant.sku }}</span
                            >
                        </button>
                    </div>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
