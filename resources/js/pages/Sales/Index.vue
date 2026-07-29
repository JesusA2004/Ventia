<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ReceiptIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
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
import { formatCurrency, formatDateTime } from '@/lib/format';
import salesRoutes from '@/routes/sales';
import type { Paginated, Sale } from '@/types';

const props = defineProps<{
    sales: Paginated<Sale>;
    filters: { folio?: string; status?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ventas', href: salesRoutes.index() }],
    },
});

const status = ref(props.filters.status ?? '');

watch(status, (value) => {
    router.get(
        salesRoutes.index.url(),
        { status: value || undefined, folio: props.filters.folio },
        { preserveState: true, replace: true },
    );
});

const columns: DataTableColumn[] = [
    { key: 'folio', label: 'Folio' },
    { key: 'created_at', label: 'Fecha' },
    { key: 'customer_name', label: 'Cliente' },
    { key: 'cashier_name', label: 'Cajero' },
    { key: 'status', label: 'Estado' },
    { key: 'total', label: 'Total', class: 'text-right' },
];

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'outline',
    suspended: 'secondary',
    completed: 'default',
    partially_returned: 'secondary',
    returned: 'secondary',
    cancelled: 'destructive',
};
</script>

<template>
    <Head title="Ventas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Historial de ventas"
            description="Consulta, cancela o revisa el detalle de cada venta."
        />

        <div class="flex flex-wrap items-center gap-3">
            <FilterBar
                :model-value="filters.folio ?? ''"
                placeholder="Buscar por folio..."
            />
            <Select v-model="status">
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Todos los estados" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="draft">Borrador</SelectItem>
                    <SelectItem value="suspended">Suspendida</SelectItem>
                    <SelectItem value="completed">Completada</SelectItem>
                    <SelectItem value="partially_returned"
                        >Devuelta parcialmente</SelectItem
                    >
                    <SelectItem value="returned">Devuelta</SelectItem>
                    <SelectItem value="cancelled">Cancelada</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <ServerDataTable :columns="columns" :paginated="props.sales">
            <template #empty>
                <EmptyState
                    :icon="ReceiptIcon"
                    title="Sin ventas"
                    description="Aún no se han registrado ventas."
                />
            </template>
            <template #cell-created_at="{ row }">
                {{ row.created_at ? formatDateTime(row.created_at) : '—' }}
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[row.status] ?? 'outline'">{{
                    row.status_label
                }}</Badge>
            </template>
            <template #cell-total="{ row }">
                <Link
                    :href="salesRoutes.show.url(row.id)"
                    class="font-medium underline-offset-2 hover:underline"
                >
                    {{ formatCurrency(row.total) }}
                </Link>
            </template>
        </ServerDataTable>
    </div>
</template>
