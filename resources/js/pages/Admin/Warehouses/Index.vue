<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageIcon, PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import WarehouseController from '@/actions/App/Http/Controllers/Settings/WarehouseController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/settings/warehouses';
import type { Paginated, Warehouse } from '@/types';

const props = defineProps<{
    warehouses: Paginated<Warehouse>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Almacenes', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'code', label: 'Código' },
    { key: 'branch_name', label: 'Sucursal' },
    { key: 'type_label', label: 'Tipo' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(warehouse: Warehouse) {
    router.delete(WarehouseController.destroy.url(warehouse.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Almacenes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Almacenes"
            description="Almacenes y puntos de existencia por sucursal."
        >
            <template #actions>
                <Button v-if="can('warehouses.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo almacén
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre o código..."
        />

        <ServerDataTable :columns="columns" :paginated="props.warehouses">
            <template #empty>
                <EmptyState
                    :icon="PackageIcon"
                    title="Sin almacenes"
                    description="Crea un almacén para poder registrar inventario y ventas."
                />
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        v-if="can('warehouses.manage')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="WarehouseController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('warehouses.manage')"
                        title="¿Eliminar almacén?"
                        :description="`Esta acción eliminará «${row.name}» de forma reversible.`"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button size="icon" variant="ghost">
                                <Trash2Icon />
                            </Button>
                        </template>
                    </ConfirmationDialog>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
