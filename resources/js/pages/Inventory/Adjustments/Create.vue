<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import StockAdjustmentController from '@/actions/App/Http/Controllers/Inventory/StockAdjustmentController';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProductPicker from '@/components/products/ProductPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { create as createRoute } from '@/routes/inventory/adjustments';
import type { Product, ProductVariant, Warehouse } from '@/types';

const props = defineProps<{
    warehouseOptions: Warehouse[];
    movementTypes: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ajustes de inventario', href: createRoute() }],
    },
});

const selectedProduct = ref<Product | null>(null);
const selectedVariant = ref<ProductVariant | null>(null);

const form = useForm({
    warehouse_id: props.warehouseOptions[0]?.id ?? null,
    product_id: null as number | null,
    product_variant_id: null as number | null,
    movement_type: props.movementTypes[0]?.value ?? 'adjustment_in',
    quantity: '',
    reason: '',
    notes: '',
});

function onProductSelected(product: Product, variant: ProductVariant | null) {
    selectedProduct.value = product;
    selectedVariant.value = variant;
    form.product_id = product.id;
    form.product_variant_id = variant?.id ?? null;
}

function submit() {
    form.post(StockAdjustmentController.store.url());
}
</script>

<template>
    <Head title="Nuevo ajuste de inventario" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Ajuste de inventario"
            description="Entradas manuales, mermas, daños, robos y uso interno."
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <FormField label="Producto" :error="form.errors.product_id">
                <ProductPicker @select="onProductSelected" />
                <p v-if="selectedProduct" class="text-sm text-muted-foreground">
                    Seleccionado: {{ selectedProduct.name
                    }}<span v-if="selectedVariant">
                        — {{ selectedVariant.label }}</span
                    >
                </p>
            </FormField>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Almacén"
                    for="warehouse_id"
                    required
                    :error="form.errors.warehouse_id"
                >
                    <Select v-model="form.warehouse_id">
                        <SelectTrigger id="warehouse_id" class="w-full">
                            <SelectValue placeholder="Selecciona un almacén" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in warehouseOptions"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>
                <FormField
                    label="Tipo de ajuste"
                    for="movement_type"
                    required
                    :error="form.errors.movement_type"
                    tooltip="Define si el ajuste suma existencias al almacén (entrada) o las resta (salida, merma, daño, extravío)."
                >
                    <Select v-model="form.movement_type">
                        <SelectTrigger id="movement_type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in movementTypes"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </FormField>
                <FormField
                    label="Cantidad"
                    for="quantity"
                    required
                    :error="form.errors.quantity"
                >
                    <Input
                        id="quantity"
                        v-model="form.quantity"
                        type="number"
                        step="0.0001"
                        min="0.0001"
                        required
                    />
                </FormField>
                <FormField
                    label="Motivo"
                    for="reason"
                    required
                    :error="form.errors.reason"
                >
                    <Input id="reason" v-model="form.reason" required />
                </FormField>
                <FormField
                    label="Notas"
                    for="notes"
                    class="sm:col-span-2"
                    :error="form.errors.notes"
                >
                    <Textarea id="notes" v-model="form.notes" rows="3" />
                </FormField>
            </div>

            <Button
                type="submit"
                :disabled="form.processing || !form.product_id"
                >Registrar ajuste</Button
            >
        </form>
    </div>
</template>
