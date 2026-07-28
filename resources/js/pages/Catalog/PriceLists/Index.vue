<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, ReceiptIcon, Trash2Icon } from '@lucide/vue';
import PriceListController from '@/actions/App/Http/Controllers/Catalog/PriceListController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/catalog/price-lists';
import type { Paginated, PriceList } from '@/types';

const props = defineProps<{
    priceLists: Paginated<PriceList>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Listas de precios', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'code', label: 'Código' },
    { key: 'currency', label: 'Moneda' },
    { key: 'priority', label: 'Prioridad' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(priceList: PriceList) {
    router.delete(PriceListController.destroy.url(priceList.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Listas de precios" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Listas de precios"
            description="Mayoreo, especiales u otras listas de precios."
        >
            <template #actions>
                <Button v-if="can('price-lists.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva lista
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre..."
        />

        <ServerDataTable :columns="columns" :paginated="props.priceLists">
            <template #empty>
                <EmptyState
                    :icon="ReceiptIcon"
                    title="Sin listas de precios"
                    description="Crea tu primera lista de precios."
                />
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip v-if="can('price-lists.manage')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar lista de precios"
                            >
                                <Link
                                    :href="PriceListController.edit.url(row.id)"
                                >
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar lista de precios</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="can('price-lists.manage')"
                        title="¿Eliminar lista de precios?"
                        :description="`No podrás eliminar «${row.name}» si tiene precios de producto asignados.`"
                        tooltip="Eliminar lista de precios"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Eliminar lista de precios"
                            >
                                <Trash2Icon />
                            </Button>
                        </template>
                    </ConfirmationDialog>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
