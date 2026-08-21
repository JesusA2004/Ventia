<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, TicketIcon, Trash2Icon } from '@lucide/vue';
import CouponController from '@/actions/App/Http/Controllers/Promotions/CouponController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/promotions/coupons';
import type { Coupon, Paginated } from '@/types';

const props = defineProps<{
    coupons: Paginated<Coupon>;
    filters: { search?: string; status?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cupones', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'code', label: 'Código' },
    { key: 'name', label: 'Nombre' },
    { key: 'type_label', label: 'Tipo' },
    { key: 'value', label: 'Valor' },
    { key: 'times_used', label: 'Usos' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function filterByStatus(value: string) {
    router.get(
        index().url,
        { ...props.filters, status: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function destroy(coupon: Coupon) {
    router.delete(CouponController.destroy.url(coupon.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Cupones" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Cupones"
            description="Códigos que el cajero puede capturar en el POS para aplicar un beneficio a la venta."
        >
            <template #actions>
                <Button v-if="can('coupons.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo cupón
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-end gap-3">
            <FilterBar
                :model-value="filters.search ?? ''"
                placeholder="Buscar por código o nombre..."
            />
            <Select
                :model-value="filters.status ?? 'all'"
                @update:model-value="
                    (v) => filterByStatus(v === 'all' ? '' : String(v ?? ''))
                "
            >
                <SelectTrigger class="w-44">
                    <SelectValue placeholder="Todos los estados" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todos los estados</SelectItem>
                    <SelectItem value="active">Activo</SelectItem>
                    <SelectItem value="inactive">Inactivo</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <ServerDataTable :columns="columns" :paginated="props.coupons">
            <template #empty>
                <EmptyState
                    :icon="TicketIcon"
                    title="Sin cupones"
                    description="Crea tu primer cupón para que el cajero pueda aplicarlo en el POS."
                />
            </template>
            <template #cell-code="{ row }">
                <span class="font-mono font-medium">{{ row.code }}</span>
            </template>
            <template #cell-value="{ row }">
                {{
                    row.type === 'percentage'
                        ? `${row.value}%`
                        : `$${row.value}`
                }}
            </template>
            <template #cell-times_used="{ row }">
                {{ row.times_used ?? 0
                }}<template v-if="row.usage_limit"
                    >/{{ row.usage_limit }}</template
                >
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip v-if="can('coupons.manage')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar cupón"
                            >
                                <Link :href="CouponController.edit.url(row.id)">
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar cupón</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="can('coupons.manage')"
                        title="¿Eliminar cupón?"
                        :description="`Se eliminará «${row.code}». Las ventas donde ya se haya usado conservan su registro histórico.`"
                        tooltip="Eliminar cupón"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Eliminar cupón"
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
