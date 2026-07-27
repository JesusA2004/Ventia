<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftRightIcon, PlusIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import { create, index, show } from '@/routes/inventory/transfers';
import type { Paginated, StockTransfer } from '@/types';

const props = defineProps<{
    transfers: Paginated<StockTransfer>;
    filters: { status?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transferencias', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'folio', label: 'Folio' },
    { key: 'origin_warehouse_name', label: 'Origen' },
    { key: 'destination_warehouse_name', label: 'Destino' },
    { key: 'status_label', label: 'Estado' },
    { key: 'created_at', label: 'Creada' },
];

function filterByStatus(value: string) {
    router.get(
        index().url,
        { status: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Transferencias" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Transferencias de inventario"
            description="Movimientos de existencias entre almacenes."
        >
            <template #actions>
                <Button v-if="can('inventory.transfer')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva transferencia
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Select
            :model-value="filters.status ?? ''"
            @update:model-value="(v) => filterByStatus(String(v ?? ''))"
        >
            <SelectTrigger class="w-56">
                <SelectValue placeholder="Todos los estados" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="">Todos los estados</SelectItem>
                <SelectItem value="draft">Borrador</SelectItem>
                <SelectItem value="pending">Pendiente</SelectItem>
                <SelectItem value="approved">Aprobada</SelectItem>
                <SelectItem value="in_transit">En tránsito</SelectItem>
                <SelectItem value="received">Recibida</SelectItem>
                <SelectItem value="partially_received"
                    >Recibida parcial</SelectItem
                >
                <SelectItem value="cancelled">Cancelada</SelectItem>
            </SelectContent>
        </Select>

        <ServerDataTable :columns="columns" :paginated="props.transfers">
            <template #empty>
                <EmptyState
                    :icon="ArrowLeftRightIcon"
                    title="Sin transferencias"
                    description="Crea tu primera transferencia entre almacenes."
                />
            </template>
            <template #cell-folio="{ row }">
                <Link
                    :href="show(row.id)"
                    class="font-medium text-primary hover:underline"
                    >{{ row.folio }}</Link
                >
            </template>
            <template #cell-status_label="{ row }">
                <Badge variant="outline">{{ row.status_label }}</Badge>
            </template>
            <template #cell-created_at="{ row }">
                {{ new Date(row.created_at).toLocaleDateString('es-MX') }}
            </template>
        </ServerDataTable>
    </div>
</template>
