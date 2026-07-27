<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { WalletIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import cash from '@/routes/cash';
import type { CashSession, Paginated } from '@/types';

defineProps<{
    sessions: Paginated<CashSession>;
    filters: { status?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cajas', href: cash.sessions.index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'register_name', label: 'Caja' },
    { key: 'user_name', label: 'Usuario' },
    { key: 'opened_at', label: 'Apertura' },
    { key: 'status', label: 'Estado' },
    { key: 'difference', label: 'Diferencia', class: 'text-right' },
];
</script>

<template>
    <Head title="Cajas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Sesiones de caja"
            description="Historial de aperturas y cierres de caja."
        >
            <template #actions>
                <Button v-if="can('cash.open')" as-child>
                    <Link :href="cash.sessions.create()">Abrir caja</Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.status ?? ''"
            placeholder="Filtrar por estado..."
        />

        <ServerDataTable :columns="columns" :paginated="sessions">
            <template #empty>
                <EmptyState
                    :icon="WalletIcon"
                    title="Sin sesiones de caja"
                    description="Aún no se ha abierto ninguna caja."
                />
            </template>
            <template #cell-opened_at="{ row }">
                {{ new Date(row.opened_at).toLocaleString() }}
            </template>
            <template #cell-status="{ row }">
                <Link :href="cash.sessions.show.url(row.id)">
                    <Badge
                        :variant="
                            row.status === 'open'
                                ? 'default'
                                : row.status === 'force_closed'
                                  ? 'destructive'
                                  : 'secondary'
                        "
                    >
                        {{ row.status_label }}
                    </Badge>
                </Link>
            </template>
            <template #cell-difference="{ row }">
                <span
                    v-if="row.difference !== null"
                    :class="
                        Number(row.difference) < 0
                            ? 'text-destructive'
                            : 'text-green-600'
                    "
                >
                    ${{ Number(row.difference).toFixed(2) }}
                </span>
                <span v-else class="text-muted-foreground">—</span>
            </template>
        </ServerDataTable>
    </div>
</template>
