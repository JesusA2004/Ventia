<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PackageSearchIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatQuantity } from '@/lib/format';
import { index } from '@/routes/inventory/balances';
import type { InventoryBalance, Paginated, Warehouse } from '@/types';

const props = defineProps<{
    balances: Paginated<InventoryBalance>;
    filters: { search?: string; warehouse_id?: string; low_stock?: string };
    warehouseOptions: Warehouse[];
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Existencias', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'product_name', label: 'Producto' },
    { key: 'warehouse_name', label: 'Almacén' },
    { key: 'lot_number', label: 'Lote' },
    { key: 'quantity', label: 'Existencia' },
    { key: 'unit_symbol', label: 'Unidad' },
    ...(can('inventory.view-costs')
        ? [{ key: 'average_cost', label: 'Costo promedio' }]
        : []),
    { key: 'is_low_stock', label: 'Estado de stock' },
];

function filterBy(key: 'warehouse_id' | 'low_stock', value: string | boolean) {
    router.get(
        index().url,
        {
            ...props.filters,
            [key]: value === '' || value === false ? undefined : value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Existencias" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Existencias"
            description="Stock actual por producto y almacén."
        />

        <div class="flex flex-wrap items-center gap-3">
            <FilterBar
                :model-value="filters.search ?? ''"
                placeholder="Buscar por nombre o SKU..."
            />

            <Select
                :model-value="filters.warehouse_id ?? ''"
                @update:model-value="
                    (v) => filterBy('warehouse_id', String(v ?? ''))
                "
            >
                <SelectTrigger class="w-56">
                    <SelectValue placeholder="Todos los almacenes" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todos los almacenes</SelectItem>
                    <SelectItem
                        v-for="option in warehouseOptions"
                        :key="option.id"
                        :value="String(option.id)"
                    >
                        {{ option.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <label class="flex items-center gap-2 text-sm">
                <Switch
                    :model-value="filters.low_stock === '1'"
                    @update:model-value="
                        (v) => filterBy('low_stock', v ? '1' : '')
                    "
                />
                Solo stock bajo
            </label>
        </div>

        <ServerDataTable :columns="columns" :paginated="props.balances">
            <template #empty>
                <EmptyState
                    :icon="PackageSearchIcon"
                    title="Sin existencias"
                    description="Aún no hay movimientos de inventario registrados."
                />
            </template>
            <template #cell-product_name="{ row }">
                <div class="flex flex-col">
                    <span>{{ row.product_name }}</span>
                    <span class="text-xs text-muted-foreground">
                        SKU {{ row.product_sku
                        }}<span v-if="row.variant_label">
                            · {{ row.variant_label }}</span
                        >
                    </span>
                </div>
            </template>
            <template #cell-lot_number="{ row }">
                {{ row.lot_number ?? '—' }}
            </template>
            <template #cell-quantity="{ row }">
                {{
                    formatQuantity(
                        row.quantity,
                        row.unit_allows_fraction ?? true,
                    )
                }}
            </template>
            <template #cell-unit_symbol="{ row }">
                {{ row.unit_symbol ?? '—' }}
            </template>
            <template #cell-average_cost="{ row }">
                {{
                    row.average_cost !== undefined
                        ? formatCurrency(row.average_cost)
                        : '—'
                }}
            </template>
            <template #cell-is_low_stock="{ row }">
                <Badge
                    :variant="row.is_low_stock ? 'destructive' : 'secondary'"
                >
                    {{ row.is_low_stock ? 'Stock bajo' : 'Stock suficiente' }}
                </Badge>
            </template>
        </ServerDataTable>
    </div>
</template>
