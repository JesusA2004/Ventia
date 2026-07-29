<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { HistoryIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/format';
import { index } from '@/routes/products';
import type { Paginated, ProductPriceHistoryEntry } from '@/types';

const props = defineProps<{
    product: { id: number; name: string; sku: string };
    history: Paginated<ProductPriceHistoryEntry>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: index() },
            { title: 'Historial de precios', href: '#' },
        ],
    },
});

const columns: DataTableColumn[] = [
    { key: 'created_at', label: 'Fecha' },
    { key: 'old_price', label: 'Precio anterior' },
    { key: 'new_price', label: 'Precio nuevo' },
    { key: 'old_cost', label: 'Costo anterior' },
    { key: 'new_cost', label: 'Costo nuevo' },
    { key: 'percentage_change', label: 'Variación' },
    { key: 'reason', label: 'Motivo' },
    { key: 'changed_by_name', label: 'Usuario' },
];

function formatDate(value: string): string {
    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head :title="`Historial de precios: ${product.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Historial de precios: ${product.name}`"
            :description="`SKU ${product.sku}. Cada cambio de precio o costo queda registrado con motivo y usuario.`"
        />

        <ServerDataTable :columns="columns" :paginated="props.history">
            <template #empty>
                <EmptyState
                    :icon="HistoryIcon"
                    title="Sin cambios registrados"
                    description="Este producto no ha tenido cambios de precio o costo."
                />
            </template>
            <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
            </template>
            <template #cell-old_price="{ row }">
                {{
                    row.old_price !== null ? formatCurrency(row.old_price) : '—'
                }}
            </template>
            <template #cell-new_price="{ row }">
                {{
                    row.new_price !== null ? formatCurrency(row.new_price) : '—'
                }}
            </template>
            <template #cell-old_cost="{ row }">
                {{ row.old_cost !== null ? formatCurrency(row.old_cost) : '—' }}
            </template>
            <template #cell-new_cost="{ row }">
                {{ row.new_cost !== null ? formatCurrency(row.new_cost) : '—' }}
            </template>
            <template #cell-percentage_change="{ row }">
                <Badge
                    v-if="row.percentage_change"
                    :variant="
                        Number(row.percentage_change) >= 0
                            ? 'default'
                            : 'destructive'
                    "
                >
                    {{ row.percentage_change }}%
                </Badge>
                <span v-else>—</span>
            </template>
            <template #cell-changed_by_name="{ row }">
                {{ row.changed_by_name ?? '—' }}
            </template>
        </ServerDataTable>
    </div>
</template>
