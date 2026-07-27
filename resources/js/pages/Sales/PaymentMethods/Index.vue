<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CreditCardIcon, PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import PaymentMethodController from '@/actions/App/Http/Controllers/Sales/PaymentMethodController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/payment-methods';
import type { PaymentMethod, Paginated } from '@/types';

const props = defineProps<{
    paymentMethods: PaymentMethod[];
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Métodos de pago', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'type_label', label: 'Tipo' },
    { key: 'affects_cash', label: 'Afecta efectivo' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

const paginated: Paginated<PaymentMethod> = {
    data: props.paymentMethods,
    links: [],
    meta: {
        current_page: 1,
        last_page: 1,
        per_page: props.paymentMethods.length || 1,
        total: props.paymentMethods.length,
        from: props.paymentMethods.length ? 1 : null,
        to: props.paymentMethods.length || null,
    },
};

function destroy(paymentMethod: PaymentMethod) {
    router.delete(PaymentMethodController.destroy.url(paymentMethod.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Métodos de pago" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Métodos de pago"
            description="Configura los métodos de pago disponibles en el POS."
        >
            <template #actions>
                <Button v-if="can('payment-methods.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo método de pago
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <ServerDataTable :columns="columns" :paginated="paginated">
            <template #empty>
                <EmptyState
                    :icon="CreditCardIcon"
                    title="Sin métodos de pago"
                    description="Crea el primero, por ejemplo Efectivo."
                />
            </template>
            <template #cell-affects_cash="{ row }">
                {{ row.affects_cash ? 'Sí' : 'No' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        v-if="can('payment-methods.manage')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="PaymentMethodController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('payment-methods.manage')"
                        title="¿Eliminar método de pago?"
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
