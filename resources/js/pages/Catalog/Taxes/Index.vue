<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PercentIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import TaxController from '@/actions/App/Http/Controllers/Catalog/TaxController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/catalog/taxes';
import type { Paginated, Tax } from '@/types';

const props = defineProps<{
    taxes: Paginated<Tax>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Impuestos', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'code', label: 'Código' },
    { key: 'rate', label: 'Tasa' },
    { key: 'type_label', label: 'Tipo' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(tax: Tax) {
    router.delete(TaxController.destroy.url(tax.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Impuestos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Impuestos"
            description="Catálogo de impuestos aplicables a productos."
        >
            <template #actions>
                <Button v-if="can('taxes.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo impuesto
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre..."
        />

        <ServerDataTable :columns="columns" :paginated="props.taxes">
            <template #empty>
                <EmptyState
                    :icon="PercentIcon"
                    title="Sin impuestos"
                    description="Crea tu primer impuesto."
                />
            </template>
            <template #cell-rate="{ row }"> {{ row.rate }}% </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        v-if="can('taxes.manage')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="TaxController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('taxes.manage')"
                        title="¿Eliminar impuesto?"
                        :description="`No podrás eliminar «${row.name}» si tiene productos relacionados.`"
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
