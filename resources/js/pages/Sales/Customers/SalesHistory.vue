<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    CalendarIcon,
    ReceiptIcon,
    TicketIcon,
    WalletIcon,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/format';
import { index } from '@/routes/customers';
import salesRoutes from '@/routes/sales';
import type { Customer, Paginated, Sale } from '@/types';

defineProps<{
    customer: Customer;
    sales: Paginated<Sale>;
    stats: {
        total_purchased: string;
        last_purchase_at: string | null;
        average_ticket: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clientes', href: index() },
            { title: 'Historial de ventas', href: '#' },
        ],
    },
});

const columns: DataTableColumn[] = [
    { key: 'folio', label: 'Folio' },
    { key: 'created_at', label: 'Fecha' },
    { key: 'status', label: 'Estado' },
    { key: 'total', label: 'Total', class: 'text-right' },
];
</script>

<template>
    <Head :title="`Historial de ${customer.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Historial de ${customer.name}`"
            description="Ventas registradas para este cliente."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="index()">
                        <ArrowLeftIcon />
                        Volver a clientes
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <WalletIcon class="size-4" /> Total comprado
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(stats.total_purchased) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TicketIcon class="size-4" /> Ticket promedio
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(stats.average_ticket) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <CalendarIcon class="size-4" /> Última compra
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{
                        stats.last_purchase_at
                            ? formatDate(stats.last_purchase_at)
                            : '—'
                    }}
                </p>
            </div>
        </div>

        <ServerDataTable :columns="columns" :paginated="sales">
            <template #empty>
                <EmptyState
                    :icon="ReceiptIcon"
                    title="Sin ventas"
                    description="Este cliente aún no tiene ventas registradas."
                />
            </template>
            <template #cell-created_at="{ row }">
                {{ formatDateTime(row.created_at) }}
            </template>
            <template #cell-status="{ row }">
                <Badge
                    :variant="
                        row.status === 'completed'
                            ? 'default'
                            : row.status === 'cancelled'
                              ? 'destructive'
                              : 'secondary'
                    "
                >
                    {{ row.status_label }}
                </Badge>
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
