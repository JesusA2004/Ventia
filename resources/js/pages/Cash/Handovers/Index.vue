<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HandCoinsIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDateTime } from '@/lib/format';
import cash from '@/routes/cash';
import type { CashHandover, Paginated } from '@/types';

defineProps<{
    handovers: Paginated<CashHandover>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: cash.sessions.index() },
            { title: 'Entregas pendientes', href: cash.handovers.index() },
        ],
    },
});

const columns: DataTableColumn[] = [
    { key: 'cashier_name', label: 'Cajero' },
    { key: 'register_name', label: 'Caja' },
    { key: 'branch_name', label: 'Sucursal' },
    { key: 'requested_at', label: 'Solicitada' },
    { key: 'difference', label: 'Diferencia', class: 'text-right' },
    { key: 'status', label: 'Estado' },
];
</script>

<template>
    <Head title="Entregas de caja pendientes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Entregas pendientes"
            description="Entregas de caja enviadas por cajeros a la espera de revisión de un supervisor."
        />

        <ServerDataTable :columns="columns" :paginated="handovers">
            <template #empty>
                <EmptyState
                    :icon="HandCoinsIcon"
                    title="Sin entregas pendientes"
                    description="No hay entregas de caja esperando revisión en este momento."
                />
            </template>
            <template #cell-requested_at="{ row }">
                {{ formatDateTime(row.requested_at) }}
            </template>
            <template #cell-difference="{ row }">
                <span
                    :class="
                        Number(row.difference) < 0
                            ? 'text-destructive'
                            : 'text-green-600'
                    "
                >
                    {{ formatCurrency(row.difference) }}
                </span>
            </template>
            <template #cell-status="{ row }">
                <Link :href="cash.handovers.show.url(row.id)">
                    <Badge variant="secondary">{{ row.status_label }}</Badge>
                </Link>
            </template>
        </ServerDataTable>
    </div>
</template>
