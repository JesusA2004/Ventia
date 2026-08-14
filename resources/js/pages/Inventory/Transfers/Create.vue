<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
    sku: string;
    quantity_requested: string;
    /** Stock at the selected origin warehouse when the product was added; null when untracked (e.g. variants base). */
    available: string | null;
};

const form = useForm({
    origin_warehouse_id: props.warehouseOptions[0]?.id ?? null,
    destination_warehouse_id: props.warehouseOptions[1]?.id ?? null,
    notes: '',
    items: [] as ItemRow[],
});

const originWarehouseName = computed(
    () =>
        props.warehouseOptions.find((w) => w.id === form.origin_warehouse_id)
            ?.name ?? 'el almacén de origen',
);

// The available quantities shown while picking come from the origin
// warehouse at the moment of selection — switching origin mid-edit would
// leave stale numbers on already-added rows, so start over instead of
// silently showing wrong availability.
watch(
    () => form.origin_warehouse_id,
    () => {
        if (form.items.length > 0) {
            form.items = [];
            toast.info(
                'Se reiniciaron los productos: cambiaste el almacén de origen.',
            );
        }
    },
);

function addItem(product: Product, variant: ProductVariant | null) {
    const available = variant?.stock ?? product.stock ?? null;

    if (available !== undefined && available !== null && Number(available) <= 0) {
        toast.error(
            `${product.name} (SKU ${variant?.sku ?? product.sku}) no tiene existencias disponibles en ${originWarehouseName.value}.`,
        );

        return;
    }

    form.items.push({
        product_id: product.id,
        product_variant_id: variant?.id ?? null,
        label: variant ? `${product.name} — ${variant.label}` : product.name,
        sku: variant?.sku ?? product.sku,
        quantity_requested: available ?? '1',
        available: available ?? null,
    });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function onQuantityInput(item: ItemRow) {
    if (item.available !== null && Number(item.quantity_requested) > Number(item.available)) {
        item.quantity_requested = item.available;
        toast.error(
            `Solo hay ${item.available} unidades disponibles de ${item.label} (SKU ${item.sku}) en ${originWarehouseName.value}.`,
        );
    }
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
                    tooltip="Almacén de donde saldrán los productos. Las cantidades disponibles mostradas corresponden a este almacén."
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
                    tooltip="Almacén al que llegarán los productos transferidos."
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
                    tooltip="Detalles adicionales opcionales sobre esta transferencia."
                >
                    <Textarea id="notes" v-model="form.notes" rows="2" />
                </FormField>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Productos a transferir</p>
                <ProductPicker
                    :warehouse-id="form.origin_warehouse_id"
                    @select="addItem"
                />
                <p class="text-xs text-muted-foreground">
                    Las cantidades disponibles corresponden a
                    {{ originWarehouseName }}.
                </p>

                <div
                    v-if="form.items.length > 0"
                    class="overflow-x-auto rounded-lg border"
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Producto</TableHead>
                                <TableHead>Disponible</TableHead>
                                <TableHead>Cantidad</TableHead>
                                <TableHead class="text-right">Quitar</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(item, index) in form.items"
                                :key="index"
                            >
                                <TableCell>
                                    {{ item.label }}
                                    <span
                                        class="block font-mono text-xs text-muted-foreground"
                                        >SKU {{ item.sku }}</span
                                    >
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ item.available ?? '—' }}
                                </TableCell>
                                <TableCell>
                                    <Input
                                        v-model="item.quantity_requested"
                                        type="number"
                                        step="0.0001"
                                        min="0.0001"
                                        :max="item.available ?? undefined"
                                        class="w-28"
                                        @change="onQuantityInput(item)"
                                    />
                                </TableCell>
                                <TableCell class="text-right">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                aria-label="Quitar producto"
                                                @click="removeItem(index)"
                                            >
                                                <Trash2Icon />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            >Quitar producto</TooltipContent
                                        >
                                    </Tooltip>
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
