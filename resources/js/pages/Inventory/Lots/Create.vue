<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProductLotController from '@/actions/App/Http/Controllers/Inventory/ProductLotController';
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
import { index } from '@/routes/inventory/lots';
import type { Product, Warehouse } from '@/types';

const props = defineProps<{
    warehouseOptions: Warehouse[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Lotes y caducidades', href: index() },
            { title: 'Nuevo lote', href: '#' },
        ],
    },
});

const selectedProduct = ref<Product | null>(null);

const form = useForm({
    product_id: null as number | null,
    lot_number: '',
    manufacture_date: '',
    expiration_date: '',
    received_at: new Date().toISOString().slice(0, 10),
    cost: '0',
    status: 'active',
    initial_quantity: '',
    warehouse_id: props.warehouseOptions[0]?.id ?? null,
});

function onProductSelected(product: Product) {
    selectedProduct.value = product;
    form.product_id = product.id;
}

function submit() {
    form.post(ProductLotController.store.url());
}
</script>

<template>
    <Head title="Nuevo lote" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo lote"
            description="Registra un lote con caducidad e inventario inicial opcional."
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <FormField
                label="Producto"
                for="product_id"
                required
                :error="form.errors.product_id"
            >
                <ProductPicker prioritize-lots @select="onProductSelected" />
                <p v-if="selectedProduct" class="text-sm text-muted-foreground">
                    Seleccionado: {{ selectedProduct.name }} ({{
                        selectedProduct.sku
                    }})
                </p>
            </FormField>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Número de lote"
                    for="lot_number"
                    required
                    :error="form.errors.lot_number"
                >
                    <Input id="lot_number" v-model="form.lot_number" required />
                </FormField>
                <FormField
                    label="Fecha de fabricación"
                    for="manufacture_date"
                    :error="form.errors.manufacture_date"
                >
                    <Input
                        id="manufacture_date"
                        v-model="form.manufacture_date"
                        type="date"
                    />
                </FormField>
                <FormField
                    label="Fecha de caducidad"
                    for="expiration_date"
                    :error="form.errors.expiration_date"
                >
                    <Input
                        id="expiration_date"
                        v-model="form.expiration_date"
                        type="date"
                    />
                </FormField>
                <FormField
                    label="Fecha de recepción"
                    for="received_at"
                    :error="form.errors.received_at"
                >
                    <Input
                        id="received_at"
                        v-model="form.received_at"
                        type="date"
                    />
                </FormField>
                <FormField
                    label="Costo"
                    for="cost"
                    required
                    :error="form.errors.cost"
                    tooltip="Costo unitario de este lote. Si registras cantidad inicial, este costo se usa para valorizar el movimiento de inventario."
                >
                    <Input
                        id="cost"
                        v-model="form.cost"
                        type="number"
                        step="0.0001"
                        min="0"
                        required
                    />
                </FormField>
                <FormField
                    label="Almacén (inventario inicial)"
                    for="warehouse_id"
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
                    label="Cantidad inicial"
                    for="initial_quantity"
                    :error="form.errors.initial_quantity"
                >
                    <Input
                        id="initial_quantity"
                        v-model="form.initial_quantity"
                        type="number"
                        step="0.0001"
                        min="0"
                        placeholder="Opcional"
                    />
                </FormField>
            </div>

            <Button
                type="submit"
                :disabled="form.processing || !form.product_id"
                >Crear lote</Button
            >
        </form>
    </div>
</template>
