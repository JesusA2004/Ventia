<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CreditCardIcon, PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import RegisterController from '@/actions/App/Http/Controllers/Settings/RegisterController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/settings/registers';
import type { CashRegister, Paginated } from '@/types';

const props = defineProps<{
    registers: Paginated<CashRegister>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cajas', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'code', label: 'Código' },
    { key: 'branch_name', label: 'Sucursal' },
    { key: 'assigned_user_name', label: 'Usuario asignado' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(register: CashRegister) {
    router.delete(RegisterController.destroy.url(register.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Cajas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Cajas"
            description="Terminales de cobro por sucursal."
        >
            <template #actions>
                <Button v-if="can('registers.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva caja
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre o código..."
        />

        <ServerDataTable :columns="columns" :paginated="props.registers">
            <template #empty>
                <EmptyState
                    :icon="CreditCardIcon"
                    title="Sin cajas"
                    description="Crea una caja para poder abrir turnos y cobrar en el POS."
                />
            </template>
            <template #cell-assigned_user_name="{ row }">
                {{ row.assigned_user_name ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        v-if="can('registers.manage')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="RegisterController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('registers.manage')"
                        title="¿Eliminar caja?"
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
