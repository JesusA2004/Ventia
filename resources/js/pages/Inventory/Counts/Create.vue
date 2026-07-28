<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Trash2Icon } from '@lucide/vue';
import StockCountController from '@/actions/App/Http/Controllers/Inventory/StockCountController';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProductPicker from '@/components/products/ProductPicker.vue';
import { Button } from '@/components/ui/button';
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
import { index } from '@/routes/inventory/counts';
import type { Product, ProductVariant, Warehouse } from '@/types';

const props = defineProps<{
    warehouseOptions: Warehouse[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Conteos físicos', href: index() },
            { title: 'Nuevo conteo', href: '#' },
        ],
    },
});

type ProductRow = {
    product_id: number;
    product_variant_id: number | null;
    label: string;
};

const form = useForm({
    warehouse_id: props.warehouseOptions[0]?.id ?? null,
    notes: '',
    products: [] as ProductRow[],
});

function addProduct(product: Product, variant: ProductVariant | null) {
    if (
        form.products.some(
            (p) =>
                p.product_id === product.id &&
                p.product_variant_id === (variant?.id ?? null),
        )
    ) {
        return;
    }

    form.products.push({
        product_id: product.id,
        product_variant_id: variant?.id ?? null,
        label: variant ? `${product.name} — ${variant.label}` : product.name,
    });
}

function removeProduct(index: number) {
    form.products.splice(index, 1);
}

function submit() {
    form.post(StockCountController.store.url());
}
</script>

<template>
    <Head title="Nuevo conteo físico" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo conteo físico"
            description="Selecciona el almacén y los productos a contar. La existencia esperada se congela al iniciar."
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
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

            <FormField label="Notas" for="notes" :error="form.errors.notes">
                <Textarea id="notes" v-model="form.notes" rows="2" />
            </FormField>

            <div class="space-y-2">
                <p class="text-sm font-medium">Productos a contar</p>
                <ProductPicker @select="addProduct" />

                <div
                    v-if="form.products.length > 0"
                    class="overflow-x-auto rounded-lg border"
                >
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Producto</TableHead>
                                <TableHead class="text-right">Quitar</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(product, index) in form.products"
                                :key="index"
                            >
                                <TableCell>{{ product.label }}</TableCell>
                                <TableCell class="text-right">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                aria-label="Quitar producto"
                                                @click="removeProduct(index)"
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
                    Agrega al menos un producto a contar.
                </p>
            </div>

            <Button
                type="submit"
                :disabled="form.processing || form.products.length === 0"
            >
                Iniciar conteo
            </Button>
        </form>
    </div>
</template>
