<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardListIcon, DownloadIcon, PlusIcon } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProductPicker from '@/components/products/ProductPicker.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatQuantity } from '@/lib/format';
import { create as createAdjustment } from '@/routes/inventory/adjustments';
import { exportMethod as kardexExport, index } from '@/routes/inventory/kardex';
import type {
    InventoryMovement,
    Paginated,
    Product,
    ProductVariant,
    Warehouse,
} from '@/types';

const props = defineProps<{
    movements: Paginated<InventoryMovement> | null;
    totals: { in: string; out: string } | null;
    filters: {
        warehouse_id?: string;
        product_id?: string;
        product_variant_id?: string;
        from?: string;
        to?: string;
    };
    warehouseOptions: Warehouse[];
    productOptions: Product[];
}>();

const { can } = usePermissions();

const selectedProductLabel = ref(props.productOptions[0]?.name ?? '');

function onProductSelected(product: Product, variant: ProductVariant | null) {
    selectedProductLabel.value = variant
        ? `${product.name} — ${variant.label}`
        : product.name;
    applyFilters({
        product_id: String(product.id),
        product_variant_id: variant ? String(variant.id) : undefined,
    });
}

function applyFilters(partial: Record<string, string | undefined>) {
    router.get(
        index().url,
        { ...props.filters, ...partial },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const columns: DataTableColumn[] = [
    { key: 'occurred_at', label: 'Fecha' },
    { key: 'movement_type_label', label: 'Tipo' },
    { key: 'direction', label: 'Entrada/Salida' },
    { key: 'quantity', label: 'Cantidad' },
    { key: 'unit_cost', label: 'Costo' },
    { key: 'resulting_stock', label: 'Existencia' },
    { key: 'reason', label: 'Motivo' },
    { key: 'performed_by_name', label: 'Usuario' },
];

function formatDate(value: string): string {
    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Movimientos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Movimientos"
            description="Historial de entradas y salidas de inventario: ventas, compras, transferencias y ajustes (entrada manual, salida manual, merma, daño, robo, caducidad, uso interno, corrección de conteo)."
        >
            <template #actions>
                <Button
                    v-if="can('inventory.adjust')"
                    as-child
                    variant="outline"
                >
                    <Link :href="createAdjustment()">
                        <PlusIcon />
                        Nuevo ajuste
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-end gap-3">
            <div class="w-80 space-y-1.5">
                <Label>Producto</Label>
                <ProductPicker @select="onProductSelected" />
                <p
                    v-if="selectedProductLabel"
                    class="text-xs text-muted-foreground"
                >
                    {{ selectedProductLabel }}
                </p>
            </div>

            <div class="w-56 space-y-1.5">
                <Label>Almacén</Label>
                <Select
                    :model-value="filters.warehouse_id ?? ''"
                    @update:model-value="
                        (v) => applyFilters({ warehouse_id: String(v ?? '') })
                    "
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona un almacén" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in warehouseOptions"
                            :key="option.id"
                            :value="String(option.id)"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-1.5">
                <Label for="from">Desde</Label>
                <Input
                    id="from"
                    type="date"
                    :model-value="filters.from ?? ''"
                    @change="
                        (e: Event) =>
                            applyFilters({
                                from: (e.target as HTMLInputElement).value,
                            })
                    "
                />
            </div>
            <div class="space-y-1.5">
                <Label for="to">Hasta</Label>
                <Input
                    id="to"
                    type="date"
                    :model-value="filters.to ?? ''"
                    @change="
                        (e: Event) =>
                            applyFilters({
                                to: (e.target as HTMLInputElement).value,
                            })
                    "
                />
            </div>

            <Button
                v-if="filters.warehouse_id && filters.product_id"
                as-child
                variant="outline"
            >
                <a :href="kardexExport.url({ query: filters })">
                    <DownloadIcon />
                    Exportar CSV
                </a>
            </Button>
        </div>

        <div v-if="totals" class="flex gap-6 rounded-lg border p-4">
            <div>
                <p class="text-xs text-muted-foreground">Total entradas</p>
                <p
                    class="text-lg font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    {{ formatQuantity(totals.in) }}
                </p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Total salidas</p>
                <p class="text-lg font-semibold text-red-600 dark:text-red-400">
                    {{ formatQuantity(totals.out) }}
                </p>
            </div>
        </div>

        <ServerDataTable
            v-if="movements"
            :columns="columns"
            :paginated="movements"
        >
            <template #empty>
                <EmptyState
                    :icon="ClipboardListIcon"
                    title="Sin movimientos"
                    description="No hay movimientos en el rango seleccionado."
                />
            </template>
            <template #cell-occurred_at="{ row }">
                {{ formatDate(row.occurred_at) }}
            </template>
            <template #cell-direction="{ row }">
                <span
                    :class="
                        row.direction === 'in'
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-red-600 dark:text-red-400'
                    "
                >
                    {{ row.direction === 'in' ? 'Entrada' : 'Salida' }}
                </span>
            </template>
            <template #cell-quantity="{ row }">
                {{ formatQuantity(row.quantity) }}
            </template>
            <template #cell-unit_cost="{ row }">
                {{ formatCurrency(row.unit_cost) }}
            </template>
            <template #cell-resulting_stock="{ row }">
                {{ formatQuantity(row.resulting_stock) }}
            </template>
            <template #cell-reason="{ row }">
                {{ row.reason ?? '—' }}
            </template>
            <template #cell-performed_by_name="{ row }">
                {{ row.performed_by_name ?? '—' }}
            </template>
        </ServerDataTable>
        <EmptyState
            v-else
            :icon="ClipboardListIcon"
            title="Selecciona un producto y un almacén"
            description="Elige un producto y un almacén para consultar su kardex."
        />
    </div>
</template>
