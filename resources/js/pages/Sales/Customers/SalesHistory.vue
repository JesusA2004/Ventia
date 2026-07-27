<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ReceiptIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { index } from '@/routes/customers';
import type { Customer, Paginated, Sale } from '@/types';

defineProps<{
    customer: Customer;
    sales: Paginated<Sale>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clientes', href: index() },
            { title: 'Historial de ventas', href: '#' },
        ],
    },
});

const columns: DataTableColumn[] = [
    { key: 'folio', label: 'Folio' },
    { key: 'created_at', label: 'Fecha' },
    { key: 'status', label: 'Estado' },
    { key: 'total', label: 'Total', class: 'text-right' },
];
</script>

<template>
    <Head title="Historial de ventas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Historial de ${customer.name}`"
            description="Ventas registradas para este cliente."
        />

        <ServerDataTable :columns="columns" :paginated="sales">
            <template #empty>
                <EmptyState
                    :icon="ReceiptIcon"
                    title="Sin ventas"
                    description="Este cliente aún no tiene ventas registradas."
                />
            </template>
            <template #cell-created_at="{ row }">
                {{ new Date(row.created_at ?? '').toLocaleString() }}
            </template>
            <template #cell-status="{ row }">
                <Badge
                    :variant="
                        row.status === 'completed'
                            ? 'default'
                            : row.status === 'cancelled'
                              ? 'destructive'
                              : 'secondary'
                    "
                >
                    {{ row.status_label }}
                </Badge>
            </template>
            <template #cell-total="{ row }">
                <Link
                    :href="`/sales/${row.id}`"
                    class="font-medium underline-offset-2 hover:underline"
                >
                    ${{ Number(row.total).toFixed(2) }}
                </Link>
            </template>
        </ServerDataTable>
    </div>
</template>
