<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { HandCoinsIcon } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatCurrency, formatDateTime } from '@/lib/format';
import cash from '@/routes/cash';
import type { CashHandover, Paginated } from '@/types';

type StatusFilter = 'pending' | 'approved' | 'rejected' | 'recount_requested';

const props = defineProps<{
    handovers: Paginated<CashHandover>;
    filters: { status: StatusFilter | 'all' };
    statusCounts: {
        all: number;
        pending: number;
        approved: number;
        rejected: number;
        recount_requested: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: cash.sessions.index() },
            { title: 'Entregas de caja', href: cash.handovers.index() },
        ],
    },
});

const TABS: { value: StatusFilter | 'all'; label: string; countKey: keyof typeof props.statusCounts }[] = [
    { value: 'pending', label: 'Pendientes', countKey: 'pending' },
    { value: 'approved', label: 'Aprobadas', countKey: 'approved' },
    { value: 'rejected', label: 'Rechazadas', countKey: 'rejected' },
    { value: 'all', label: 'Todas', countKey: 'all' },
];

const activeTab = computed(() => props.filters.status);

function switchTab(value: string) {
    router.get(
        cash.handovers.index.url(),
        { status: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const baseColumns: DataTableColumn[] = [
    { key: 'cashier_name', label: 'Cajero' },
    { key: 'register_name', label: 'Caja' },
    { key: 'branch_name', label: 'Sucursal' },
    { key: 'requested_at', label: 'Solicitada' },
    { key: 'difference', label: 'Diferencia', class: 'text-right' },
];

const resolvedColumns: DataTableColumn[] = [
    { key: 'approver_name', label: 'Supervisor' },
    { key: 'resolved_at', label: 'Resuelta' },
];

const statusColumn: DataTableColumn = { key: 'status', label: 'Estado' };

const columns = computed<DataTableColumn[]>(() =>
    activeTab.value === 'pending'
        ? [...baseColumns, statusColumn]
        : [...baseColumns, ...resolvedColumns, statusColumn],
);

const emptyDescription = computed(() => {
    switch (activeTab.value) {
        case 'approved':
            return 'No hay entregas de caja aprobadas todavía.';
        case 'rejected':
            return 'No hay entregas de caja rechazadas.';
        case 'all':
            return 'Todavía no se ha enviado ninguna entrega de caja.';
        default:
            return 'No hay entregas de caja esperando revisión en este momento.';
    }
});
</script>

<template>
    <Head title="Entregas de caja" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Entregas de caja"
            description="Historial de entregas de caja: pendientes de revisión, aprobadas y rechazadas por un supervisor."
        />

        <Tabs :model-value="activeTab" @update:model-value="(v) => switchTab(String(v))">
            <TabsList class="flex-wrap">
                <TabsTrigger v-for="tab in TABS" :key="tab.value" :value="tab.value">
                    {{ tab.label }}
                    <Badge variant="secondary" class="ml-1.5">
                        {{ statusCounts[tab.countKey] }}
                    </Badge>
                </TabsTrigger>
            </TabsList>
        </Tabs>

        <ServerDataTable :columns="columns" :paginated="handovers">
            <template #empty>
                <EmptyState
                    :icon="HandCoinsIcon"
                    title="Sin entregas"
                    :description="emptyDescription"
                />
            </template>
            <template #cell-requested_at="{ row }">
                {{ formatDateTime(row.requested_at) }}
            </template>
            <template #cell-resolved_at="{ row }">
                {{ row.resolved_at ? formatDateTime(row.resolved_at) : '—' }}
            </template>
            <template #cell-approver_name="{ row }">
                {{ row.approver_name ?? '—' }}
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
