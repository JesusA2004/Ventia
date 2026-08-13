<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PencilIcon,
    PlusIcon,
    SlidersHorizontalIcon,
    Trash2Icon,
} from '@lucide/vue';
import UnitController from '@/actions/App/Http/Controllers/Catalog/UnitController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/catalog/units';
import type { Paginated, Unit } from '@/types';

const props = defineProps<{
    units: Paginated<Unit>;
    filters: { search?: string };
}>();

const { can, isSuperAdmin } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Unidades', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'symbol', label: 'Símbolo' },
    { key: 'type_label', label: 'Tipo' },
    { key: 'is_global', label: 'Origen' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(unit: Unit) {
    router.delete(UnitController.destroy.url(unit.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Unidades" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Unidades de medida"
            description="Unidades para vender por pieza, peso, volumen u otros."
        >
            <template #actions>
                <Button v-if="can('units.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva unidad
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre..."
        />

        <ServerDataTable :columns="columns" :paginated="props.units">
            <template #empty>
                <EmptyState
                    :icon="SlidersHorizontalIcon"
                    title="Sin unidades"
                    description="Crea tu primera unidad de medida."
                />
            </template>
            <template #cell-is_global="{ row }">
                <Badge :variant="row.is_global ? 'outline' : 'secondary'">
                    {{ row.is_global ? 'Global' : 'Propia' }}
                </Badge>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip
                        v-if="
                            can('units.manage') &&
                            (!row.is_global || isSuperAdmin)
                        "
                    >
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar unidad"
                            >
                                <Link :href="UnitController.edit.url(row.id)">
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar unidad</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="
                            can('units.manage') &&
                            (!row.is_global || isSuperAdmin)
                        "
                        title="¿Eliminar unidad?"
                        :description="`No podrás eliminar «${row.name}» si tiene productos relacionados.`"
                        tooltip="Eliminar unidad"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Eliminar unidad"
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
