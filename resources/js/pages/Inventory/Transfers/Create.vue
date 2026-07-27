<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import StockTransferController from '@/actions/App/Http/Controllers/Inventory/StockTransferController';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/inventory/transfers';
import type { Product, ProductVariant, Warehouse } from '@/types';

const props = defineProps<{
    warehouseOptions: Warehouse[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transferencias', href: index() },
            { title: 'Nueva transferencia', href: '#' },
        ],
    },
});

type ItemRow = {
    product_id: number;
    product_variant_id: number | null;
    label: string;
    quantity_requested: string;
};

const form = useForm({
    origin_warehouse_id: props.warehouseOptions[0]?.id ?? null,
    destination_warehouse_id: props.warehouseOptions[1]?.id ?? null,
    notes: '',
    items: [] as ItemRow[],
});

function addItem(product: Product, variant: ProductVariant | null) {
    form.items.push({
        product_id: product.id,
        product_variant_id: variant?.id ?? null,
        label: variant ? `${product.name} — ${variant.label}` : product.name,
        quantity_requested: '1',
    });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function submit() {
    form.post(StockTransferController.store.url());
}
</script>

<template>
    <Head title="Nueva transferencia" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva transferencia"
            description="Envía productos de un almacén a otro."
        />

        <form class="max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Almacén de origen"
                    for="origin"
                    required
                    :error="form.errors.origin_warehouse_id"
                >
                    <Select v-model="form.origin_warehouse_id">
                        <SelectTrigger id="origin" class="w-full">
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
                    label="Almacén de destino"
                    for="destination"
                    required
                    :error="form.errors.destination_warehouse_id"
                >
                    <Select v-model="form.destination_warehouse_id">
                        <SelectTrigger id="destination" class="w-full">
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
                    label="Notas"
                    for="notes"
                    class="sm:col-span-2"
                    :error="form.errors.notes"
                >
                    <Textarea id="notes" v-model="form.notes" rows="2" />
                </FormField>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Productos a transferir</p>
                <ProductPicker @select="addItem" />

                <div
                    v-if="form.items.length > 0"
                    class="overflow-x-auto rounded-lg border"
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Producto</TableHead>
                                <TableHead>Cantidad</TableHead>
                                <TableHead class="text-right">Quitar</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(item, index) in form.items"
                                :key="index"
                            >
                                <TableCell>{{ item.label }}</TableCell>
                                <TableCell>
                                    <Input
                                        v-model="item.quantity_requested"
                                        type="number"
                                        step="0.0001"
                                        min="0.0001"
                                        class="w-28"
                                    />
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        @click="removeItem(index)"
                                    >
                                        <Trash2Icon />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    Agrega al menos un producto.
                </p>
            </div>

            <Button
                type="submit"
                :disabled="form.processing || form.items.length === 0"
                >Crear transferencia (borrador)</Button
            >
        </form>
    </div>
</template>
