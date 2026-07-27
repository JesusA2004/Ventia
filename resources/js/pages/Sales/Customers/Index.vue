<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, Trash2Icon, UsersIcon } from '@lucide/vue';
import CustomerController from '@/actions/App/Http/Controllers/Sales/CustomerController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/customers';
import type { Customer, Paginated } from '@/types';

const props = defineProps<{
    customers: Paginated<Customer>;
    filters: { search?: string; status?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clientes', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'customer_type_label', label: 'Tipo' },
    { key: 'phone', label: 'Teléfono' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(customer: Customer) {
    router.delete(CustomerController.destroy.url(customer.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Clientes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Clientes"
            description="Administra los clientes disponibles para venta."
        >
            <template #actions>
                <Button v-if="can('customers.create')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo cliente
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre, teléfono, RFC o correo..."
        />

        <ServerDataTable :columns="columns" :paginated="props.customers">
            <template #empty>
                <EmptyState
                    :icon="UsersIcon"
                    title="Sin clientes"
                    description="Crea tu primer cliente."
                />
            </template>
            <template #cell-phone="{ row }">
                {{ row.phone ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button as-child size="icon" variant="ghost">
                        <Link :href="`/customers/${row.id}/sales`">
                            <UsersIcon />
                        </Link>
                    </Button>
                    <Button
                        v-if="can('customers.update')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="CustomerController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('customers.delete')"
                        title="¿Eliminar cliente?"
                        :description="`No podrás eliminar «${row.name}» si tiene ventas registradas.`"
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
