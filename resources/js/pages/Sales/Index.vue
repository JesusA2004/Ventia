<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ReceiptIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SearchInput from '@/components/filters/SearchInput.vue';
import SearchableSelect from '@/components/forms/SearchableSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatCurrency, formatDateTime } from '@/lib/format';
import salesRoutes from '@/routes/sales';
import type { Paginated, Sale } from '@/types';

const props = defineProps<{
    sales: Paginated<Sale>;
    filters: {
        folio?: string;
        status?: string;
        cashier_id?: number;
        register_id?: number;
        date_from?: string;
        date_to?: string;
    };
    cashierOptions: { id: number; name: string }[];
    registerOptions: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ventas', href: salesRoutes.index() }],
    },
});

const status = ref(props.filters.status ?? '');
const folio = ref(props.filters.folio ?? '');

function apply(partial: Record<string, string | number | undefined>) {
    router.get(
        salesRoutes.index.url(),
        { ...props.filters, ...partial },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(status, (value) => apply({ status: value || undefined }));
watch(folio, (value) => apply({ folio: value || undefined }));

const columns: DataTableColumn[] = [
    { key: 'folio', label: 'Folio' },
    { key: 'created_at', label: 'Fecha' },
    { key: 'customer_name', label: 'Cliente' },
    { key: 'cashier_name', label: 'Cajero' },
    { key: 'status', label: 'Estado' },
    { key: 'total', label: 'Total', class: 'text-right' },
];

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'outline',
    suspended: 'secondary',
    completed: 'default',
    partially_returned: 'secondary',
    returned: 'secondary',
    cancelled: 'destructive',
};
</script>

<template>
    <Head title="Ventas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Historial de ventas"
            description="Consulta, cancela o revisa el detalle de cada venta."
        />

        <div class="flex flex-wrap items-center gap-3">
            <SearchInput v-model="folio" placeholder="Buscar por folio..." />
            <Select v-model="status">
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Todos los estados" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="draft">Borrador</SelectItem>
                    <SelectItem value="suspended">Suspendida</SelectItem>
                    <SelectItem value="completed">Completada</SelectItem>
                    <SelectItem value="partially_returned"
                        >Devuelta parcialmente</SelectItem
                    >
                    <SelectItem value="returned">Devuelta</SelectItem>
                    <SelectItem value="cancelled">Cancelada</SelectItem>
                </SelectContent>
            </Select>
            <SearchableSelect
                v-if="cashierOptions.length"
                class="w-48"
                :model-value="
                    filters.cashier_id ? String(filters.cashier_id) : null
                "
                :options="
                    cashierOptions.map((c) => ({
                        value: String(c.id),
                        label: c.name,
                    }))
                "
                placeholder="Cajero"
                all-label="Todos los cajeros"
                @update:model-value="
                    (v) => apply({ cashier_id: v ?? undefined })
                "
            />
            <SearchableSelect
                v-if="registerOptions.length"
                class="w-48"
                :model-value="
                    filters.register_id ? String(filters.register_id) : null
                "
                :options="
                    registerOptions.map((r) => ({
                        value: String(r.id),
                        label: r.name,
                    }))
                "
                placeholder="Caja"
                all-label="Todas las cajas"
                @update:model-value="
                    (v) => apply({ register_id: v ?? undefined })
                "
            />
            <Input
                type="date"
                class="w-40"
                :model-value="filters.date_from ?? ''"
                @change="
                    (e: Event) =>
                        apply({
                            date_from:
                                (e.target as HTMLInputElement).value ||
                                undefined,
                        })
                "
            />
            <Input
                type="date"
                class="w-40"
                :model-value="filters.date_to ?? ''"
                @change="
                    (e: Event) =>
                        apply({
                            date_to:
                                (e.target as HTMLInputElement).value ||
                                undefined,
                        })
                "
            />
        </div>

        <ServerDataTable :columns="columns" :paginated="props.sales">
            <template #empty>
                <EmptyState
                    :icon="ReceiptIcon"
                    title="Sin ventas"
                    description="Aún no se han registrado ventas."
                />
            </template>
            <template #cell-created_at="{ row }">
                {{ row.created_at ? formatDateTime(row.created_at) : '—' }}
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[row.status] ?? 'outline'">{{
                    row.status_label
                }}</Badge>
            </template>
            <template #cell-total="{ row }">
                <Link
                    :href="salesRoutes.show.url(row.id)"
                    class="font-medium underline-offset-2 hover:underline"
                >
                    {{ formatCurrency(row.total) }}
                </Link>
            </template>
        </ServerDataTable>
    </div>
</template>
