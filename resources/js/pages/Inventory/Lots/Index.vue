<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArchiveIcon, PencilIcon, PlusIcon } from '@lucide/vue';
import ProductLotController from '@/actions/App/Http/Controllers/Inventory/ProductLotController';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/inventory/lots';
import type { Paginated, ProductLot } from '@/types';

const props = defineProps<{
    lots: Paginated<ProductLot>;
    filters: { search?: string; expiring_soon?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Lotes y caducidades', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'product_name', label: 'Producto' },
    { key: 'lot_number', label: 'Lote' },
    { key: 'expiration_date', label: 'Caducidad' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function toggleExpiringSoon(value: boolean) {
    router.get(
        index().url,
        { ...props.filters, expiring_soon: value ? '1' : undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Lotes y caducidades" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Lotes y caducidades"
            description="Trazabilidad por lote y alertas de vencimiento."
        >
            <template #actions>
                <Button v-if="can('inventory.adjust')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo lote
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-3">
            <FilterBar
                :model-value="filters.search ?? ''"
                placeholder="Buscar por producto o lote..."
            />
            <label class="flex items-center gap-2 text-sm">
                <Switch
                    :model-value="filters.expiring_soon === '1'"
                    @update:model-value="toggleExpiringSoon"
                />
                Por vencer (30 días)
            </label>
        </div>

        <ServerDataTable :columns="columns" :paginated="props.lots">
            <template #empty>
                <EmptyState
                    :icon="ArchiveIcon"
                    title="Sin lotes"
                    description="Registra tu primer lote."
                />
            </template>
            <template #cell-expiration_date="{ row }">
                <span v-if="!row.expiration_date">—</span>
                <Badge
                    v-else
                    :variant="row.is_expired ? 'destructive' : 'outline'"
                    >{{ row.expiration_date }}</Badge
                >
            </template>
            <template #cell-status="{ row }">
                <Badge
                    :variant="row.status === 'active' ? 'default' : 'secondary'"
                    >{{ row.status_label }}</Badge
                >
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip v-if="can('inventory.adjust')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar lote"
                            >
                                <Link
                                    :href="
                                        ProductLotController.edit.url(row.id)
                                    "
                                >
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar lote</TooltipContent>
                    </Tooltip>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
