<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, TagIcon, Trash2Icon } from '@lucide/vue';
import PromotionController from '@/actions/App/Http/Controllers/Promotions/PromotionController';
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
import { create, index } from '@/routes/promotions/promotions';
import type { Paginated, Promotion } from '@/types';

const props = defineProps<{
    promotions: Paginated<Promotion>;
    filters: { search?: string; status?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Promociones', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'type_label', label: 'Tipo' },
    { key: 'value', label: 'Valor' },
    { key: 'priority', label: 'Prioridad' },
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

function destroy(promotion: Promotion) {
    router.delete(PromotionController.destroy.url(promotion.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Promociones" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Promociones"
            description="Reglas de descuento que se aplican automáticamente en el POS cuando una venta cumple las condiciones."
        >
            <template #actions>
                <Button v-if="can('promotions.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva promoción
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-end gap-3">
            <FilterBar
                :model-value="filters.search ?? ''"
                placeholder="Buscar por nombre..."
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

        <ServerDataTable :columns="columns" :paginated="props.promotions">
            <template #empty>
                <EmptyState
                    :icon="TagIcon"
                    title="Sin promociones"
                    description="Crea tu primera promoción para aplicar descuentos automáticos en el POS."
                />
            </template>
            <template #cell-name="{ row }">
                <div class="flex flex-col">
                    <span class="font-medium">{{ row.name }}</span>
                    <Badge
                        v-if="row.status === 'active' && !row.is_active_now"
                        variant="outline"
                        class="mt-1 w-fit text-xs"
                        >Fuera de vigencia</Badge
                    >
                </div>
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
                    <Tooltip v-if="can('promotions.manage')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar promoción"
                            >
                                <Link
                                    :href="PromotionController.edit.url(row.id)"
                                >
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar promoción</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="can('promotions.manage')"
                        title="¿Eliminar promoción?"
                        :description="`Se eliminará «${row.name}». Las ventas donde ya se haya aplicado conservan su registro histórico.`"
                        tooltip="Eliminar promoción"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Eliminar promoción"
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
