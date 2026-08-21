<script setup lang="ts">
import { XIcon } from '@lucide/vue';
import { ref } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import ProductPicker from '@/components/products/ProductPicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type {
    Product,
    ProductVariant,
    PromotionScopeOption,
    PromotionScopeProduct,
} from '@/types';

const props = defineProps<{
    branchOptions: PromotionScopeOption[];
    categoryOptions: PromotionScopeOption[];
    /** Products already selected (edit mode) — chips need name/sku without a re-search. */
    initialProducts?: PromotionScopeProduct[];
}>();

const branchIds = defineModel<number[]>('branchIds', { default: () => [] });
const categoryIds = defineModel<number[]>('categoryIds', {
    default: () => [],
});
const productIds = defineModel<number[]>('productIds', { default: () => [] });

const selectedProducts = ref<PromotionScopeProduct[]>(
    props.initialProducts ?? [],
);

function toggleBranch(id: number) {
    branchIds.value = branchIds.value.includes(id)
        ? branchIds.value.filter((existing) => existing !== id)
        : [...branchIds.value, id];
}

function toggleCategory(id: number) {
    categoryIds.value = categoryIds.value.includes(id)
        ? categoryIds.value.filter((existing) => existing !== id)
        : [...categoryIds.value, id];
}

function addProduct(product: Product, variant: ProductVariant | null) {
    const id = variant?.id ?? product.id;

    if (productIds.value.includes(id)) {
        return;
    }

    productIds.value = [...productIds.value, id];
    selectedProducts.value = [
        ...selectedProducts.value,
        { id, name: product.name, sku: variant?.sku ?? product.sku },
    ];
}

function removeProduct(id: number) {
    productIds.value = productIds.value.filter((existing) => existing !== id);
    selectedProducts.value = selectedProducts.value.filter(
        (product) => product.id !== id,
    );
}
</script>

<template>
    <div class="space-y-4">
        <FormField
            label="Sucursales aplicables"
            tooltip="Deja todas sin marcar para que la promoción aplique en cualquier sucursal. Si marcas una o más, solo aplicará en esas."
        >
            <div
                v-if="branchOptions.length > 0"
                class="grid gap-2 rounded-lg border p-3 sm:grid-cols-2"
            >
                <div
                    v-for="branch in branchOptions"
                    :key="branch.id"
                    class="flex items-center gap-1.5"
                >
                    <Checkbox
                        :id="`branch-${branch.id}`"
                        :model-value="branchIds.includes(branch.id)"
                        @update:model-value="() => toggleBranch(branch.id)"
                    />
                    <Label :for="`branch-${branch.id}`" class="font-normal">{{
                        branch.name
                    }}</Label>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No hay sucursales registradas.
            </p>
        </FormField>

        <FormField
            label="Categorías aplicables"
            tooltip="Deja todas sin marcar para que aplique a cualquier categoría. Si marcas una o más categorías (o agregas productos abajo), solo aplicará a esos."
        >
            <div
                v-if="categoryOptions.length > 0"
                class="grid max-h-48 gap-2 overflow-y-auto rounded-lg border p-3 sm:grid-cols-2"
            >
                <div
                    v-for="category in categoryOptions"
                    :key="category.id"
                    class="flex items-center gap-1.5"
                >
                    <Checkbox
                        :id="`category-${category.id}`"
                        :model-value="categoryIds.includes(category.id)"
                        @update:model-value="() => toggleCategory(category.id)"
                    />
                    <Label
                        :for="`category-${category.id}`"
                        class="font-normal"
                        >{{ category.name }}</Label
                    >
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No hay categorías registradas.
            </p>
        </FormField>

        <FormField
            label="Productos específicos"
            tooltip="Agrega productos individuales si la promoción no debe aplicar a la categoría completa. Se combina con las categorías marcadas arriba (aplica si el producto coincide con cualquiera de las dos)."
        >
            <div class="space-y-2">
                <ProductPicker @select="addProduct" />
                <div
                    v-if="selectedProducts.length > 0"
                    class="flex flex-wrap gap-2"
                >
                    <Badge
                        v-for="product in selectedProducts"
                        :key="product.id"
                        variant="secondary"
                        class="gap-1 pr-1"
                    >
                        {{ product.name }}
                        <span class="text-xs text-muted-foreground"
                            >({{ product.sku }})</span
                        >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-4"
                            :aria-label="`Quitar ${product.name}`"
                            @click="removeProduct(product.id)"
                        >
                            <XIcon class="size-3" />
                        </Button>
                    </Badge>
                </div>
            </div>
        </FormField>
    </div>
</template>
