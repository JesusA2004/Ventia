<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2Icon, PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import BranchController from '@/actions/App/Http/Controllers/Settings/BranchController';
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
import { create, index } from '@/routes/settings/branches';
import type { Branch, Paginated } from '@/types';

const props = defineProps<{
    branches: Paginated<Branch>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sucursales', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'code', label: 'Código' },
    { key: 'warehouses_count', label: 'Almacenes' },
    { key: 'registers_count', label: 'Cajas' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(branch: Branch) {
    router.delete(BranchController.destroy.url(branch.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Sucursales" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Sucursales"
            description="Sucursales físicas de tu empresa."
        >
            <template #actions>
                <Button v-if="can('branches.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva sucursal
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre o código..."
        />

        <ServerDataTable :columns="columns" :paginated="props.branches">
            <template #empty>
                <EmptyState
                    :icon="Building2Icon"
                    title="Sin sucursales"
                    description="Crea tu primera sucursal para empezar a operar."
                />
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip v-if="can('branches.manage')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar sucursal"
                            >
                                <Link :href="BranchController.edit.url(row.id)">
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar sucursal</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="can('branches.manage')"
                        title="¿Eliminar sucursal?"
                        :description="`Esta acción eliminará «${row.name}» de forma reversible.`"
                        tooltip="Eliminar sucursal"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Eliminar sucursal"
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
