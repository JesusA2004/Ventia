<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardListIcon, EyeIcon, PlusIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
import { create, index, show } from '@/routes/inventory/counts';
import type { Paginated, StockCount } from '@/types';

const props = defineProps<{
    counts: Paginated<StockCount>;
    filters: { status?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Conteos físicos', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'folio', label: 'Folio' },
    { key: 'warehouse_name', label: 'Almacén' },
    { key: 'status_label', label: 'Estado' },
    { key: 'created_at', label: 'Creado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function filterByStatus(value: string) {
    router.get(
        index().url,
        { status: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Conteos físicos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Conteos físicos"
            description="Congela existencia esperada, captura conteo y aplica diferencias."
        >
            <template #actions>
                <Button v-if="can('inventory.count')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo conteo
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Select
            :model-value="filters.status ?? 'all'"
            @update:model-value="
                (v) => filterByStatus(v === 'all' ? '' : String(v ?? ''))
            "
        >
            <SelectTrigger class="w-56">
                <SelectValue placeholder="Todos los estados" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todos los estados</SelectItem>
                <SelectItem value="counting">Contando</SelectItem>
                <SelectItem value="completed">Completado</SelectItem>
                <SelectItem value="applied">Aplicado</SelectItem>
                <SelectItem value="cancelled">Cancelado</SelectItem>
            </SelectContent>
        </Select>

        <ServerDataTable :columns="columns" :paginated="props.counts">
            <template #empty>
                <EmptyState
                    :icon="ClipboardListIcon"
                    title="Sin conteos"
                    description="Inicia tu primer conteo físico."
                />
            </template>
            <template #cell-folio="{ row }">
                <Link
                    :href="show(row.id)"
                    class="font-medium text-primary hover:underline"
                    >{{ row.folio }}</Link
                >
            </template>
            <template #cell-status_label="{ row }">
                <Badge variant="outline">{{ row.status_label }}</Badge>
            </template>
            <template #cell-created_at="{ row }">
                {{ new Date(row.created_at).toLocaleDateString('es-MX') }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Ver detalle del conteo"
                            >
                                <Link :href="show(row.id)">
                                    <EyeIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Ver detalle</TooltipContent>
                    </Tooltip>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
