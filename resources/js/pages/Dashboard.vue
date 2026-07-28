<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangleIcon,
    BanknoteIcon,
    PackageIcon,
    ReceiptIcon,
    RotateCcwIcon,
    ScaleIcon,
    TicketIcon,
    TrendingUpIcon,
    WalletIcon,
    XCircleIcon,
} from '@lucide/vue';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip as ChartTooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import ChartCard from '@/components/dashboard/ChartCard.vue';
import DashboardFilters from '@/components/dashboard/DashboardFilters.vue';
import MetricCard from '@/components/dashboard/MetricCard.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatQuantity } from '@/lib/format';
import { dashboard } from '@/routes';
import type { Branch, CashRegister } from '@/types';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    ChartTooltip,
    Legend,
    Filler,
);

type Metrics = {
    sales_count: number;
    sales_total: string;
    ticket_average: string;
    discount_total: string;
    profit_total: string | null;
    cash_income: string;
    card_income: string;
    products_sold: string;
    cancelled_count: number;
    returns_count: number;
    expected_cash: string;
    cash_difference: string;
    cash_session_open: boolean;
    low_stock_count: number;
    expiring_lots_count: number;
    comparison: {
        sales_total: number | null;
        sales_count: number | null;
        ticket_average: number | null;
        profit_total: number | null;
    };
    sales_over_time: { date: string; total: string; tickets: number }[];
    sales_by_branch: { name: string; total: string }[];
    payment_method_breakdown: { method: string; total: string }[];
    top_products: { name: string; quantity: string }[];
    top_categories: { name: string; total: string }[];
};

type Filters = {
    preset: string;
    date_from: string;
    date_to: string;
    granularity: 'hour' | 'day' | 'week';
    branch_id: number | null;
    register_id: number | null;
    cashier_id: number | null;
};

const props = defineProps<{
    metrics: Metrics;
    filters: Filters;
    branchOptions: Branch[];
    registerOptions: CashRegister[];
    cashierOptions: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const page = usePage();
const activeCompanyName = computed(() => page.props.activeCompany?.name);

function apply(partial: Record<string, string | number | undefined>) {
    router.get(
        dashboard().url,
        { ...props.filters, ...partial },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function reset() {
    router.get(
        dashboard().url,
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const granularityOptions: { value: Filters['granularity']; label: string }[] = [
    { value: 'hour', label: 'Por hora' },
    { value: 'day', label: 'Por día' },
    { value: 'week', label: 'Por semana' },
];

function timeLabel(date: string, granularity: Filters['granularity']): string {
    if (granularity === 'hour') {
        return date.slice(11, 16);
    }

    return date.slice(5).split('-').reverse().join('/');
}

const salesOverTimeChart = computed(() => ({
    labels: props.metrics.sales_over_time.map((d) =>
        timeLabel(d.date, props.filters.granularity),
    ),
    datasets: [
        {
            label: 'Ventas',
            data: props.metrics.sales_over_time.map((d) => Number(d.total)),
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.15)',
            fill: true,
            tension: 0.3,
        },
    ],
}));

const salesTrend = computed(() =>
    props.metrics.sales_over_time.slice(-14).map((d) => Number(d.total)),
);

const branchChart = computed(() => ({
    labels: props.metrics.sales_by_branch.map((b) => b.name),
    datasets: [
        {
            label: 'Ventas',
            data: props.metrics.sales_by_branch.map((b) => Number(b.total)),
            backgroundColor: '#4f46e5',
        },
    ],
}));

const paymentChart = computed(() => ({
    labels: props.metrics.payment_method_breakdown.map((p) => p.method),
    datasets: [
        {
            data: props.metrics.payment_method_breakdown.map((p) =>
                Number(p.total),
            ),
            backgroundColor: [
                '#4f46e5',
                '#22c55e',
                '#f59e0b',
                '#ef4444',
                '#06b6d4',
                '#a855f7',
            ],
        },
    ],
}));

const topProductsChart = computed(() => ({
    labels: props.metrics.top_products.map((p) => p.name),
    datasets: [
        {
            label: 'Unidades',
            data: props.metrics.top_products.map((p) => Number(p.quantity)),
            backgroundColor: '#22c55e',
        },
    ],
}));

const topCategoriesChart = computed(() => ({
    labels: props.metrics.top_categories.map((c) => c.name),
    datasets: [
        {
            label: 'Ingresos',
            data: props.metrics.top_categories.map((c) => Number(c.total)),
            backgroundColor: '#f59e0b',
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
};

const horizontalBarOptions = { ...chartOptions, indexAxis: 'y' as const };
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6">
        <DashboardFilters
            :filters="filters"
            :branch-options="branchOptions"
            :register-options="registerOptions"
            :cashier-options="cashierOptions"
            :active-company-name="activeCompanyName"
            @update="apply"
            @reset="reset"
        />

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <MetricCard
                :icon="ReceiptIcon"
                label="Ventas netas"
                :value="formatCurrency(metrics.sales_total)"
                :comparison="metrics.comparison.sales_total"
                :trend="salesTrend"
                tooltip="Suma de ventas completadas en el período, sin cancelaciones."
            />
            <MetricCard
                :icon="TicketIcon"
                label="Tickets"
                :value="String(metrics.sales_count)"
                :comparison="metrics.comparison.sales_count"
                tooltip="Número de ventas completadas en el período."
            />
            <MetricCard
                :icon="TicketIcon"
                label="Ticket promedio"
                :value="formatCurrency(metrics.ticket_average)"
                :comparison="metrics.comparison.ticket_average"
                tooltip="Ventas netas entre número de tickets."
            />
            <MetricCard
                v-if="metrics.profit_total !== null"
                :icon="TrendingUpIcon"
                label="Utilidad"
                :value="formatCurrency(metrics.profit_total)"
                :comparison="metrics.comparison.profit_total"
                tooltip="Ventas menos costo de los productos vendidos."
            />
        </div>

        <ChartCard
            title="Ventas en el tiempo"
            :empty="metrics.sales_over_time.every((d) => Number(d.total) === 0)"
        >
            <template #actions>
                <div class="flex gap-1 rounded-md border p-0.5">
                    <Button
                        v-for="option in granularityOptions"
                        :key="option.value"
                        size="xs"
                        :variant="
                            filters.granularity === option.value
                                ? 'default'
                                : 'ghost'
                        "
                        @click="apply({ granularity: option.value })"
                    >
                        {{ option.label }}
                    </Button>
                </div>
            </template>
            <Line :data="salesOverTimeChart" :options="chartOptions" />
        </ChartCard>

        <div class="grid gap-4 lg:grid-cols-2">
            <ChartCard
                title="Métodos de pago"
                :empty="metrics.payment_method_breakdown.length === 0"
            >
                <Doughnut
                    :data="paymentChart"
                    :options="{ responsive: true, maintainAspectRatio: false }"
                />
            </ChartCard>
            <ChartCard
                title="Productos más vendidos"
                :empty="metrics.top_products.length === 0"
            >
                <Bar :data="topProductsChart" :options="horizontalBarOptions" />
            </ChartCard>
            <ChartCard
                title="Categorías más vendidas"
                :empty="metrics.top_categories.length === 0"
            >
                <Bar :data="topCategoriesChart" :options="chartOptions" />
            </ChartCard>
            <ChartCard
                title="Ventas por sucursal"
                :empty="metrics.sales_by_branch.length === 0"
            >
                <Bar :data="branchChart" :options="chartOptions" />
            </ChartCard>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <WalletIcon class="size-4" /> Mi caja
                </div>
                <p
                    class="mt-1 text-lg font-bold"
                    :class="
                        metrics.cash_session_open
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-muted-foreground'
                    "
                >
                    {{ metrics.cash_session_open ? 'Abierta' : 'Cerrada' }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <ScaleIcon class="size-4" /> Diferencia de caja
                </div>
                <p
                    class="mt-1 text-lg font-bold"
                    :class="
                        Number(metrics.cash_difference) === 0
                            ? ''
                            : Number(metrics.cash_difference) > 0
                              ? 'text-emerald-600 dark:text-emerald-400'
                              : 'text-red-600 dark:text-red-400'
                    "
                >
                    {{ formatCurrency(metrics.cash_difference) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <XCircleIcon class="size-4" /> Canceladas
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ metrics.cancelled_count }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <RotateCcwIcon class="size-4" /> Devoluciones
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ metrics.returns_count }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <PackageIcon class="size-4" /> Stock bajo
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ metrics.low_stock_count }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <AlertTriangleIcon class="size-4" /> Por caducar
                </div>
                <p
                    class="mt-1 text-lg font-bold"
                    :class="
                        metrics.expiring_lots_count > 0
                            ? 'text-amber-600 dark:text-amber-400'
                            : ''
                    "
                >
                    {{ metrics.expiring_lots_count }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <BanknoteIcon class="size-4" /> Efectivo
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ formatCurrency(metrics.cash_income) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <BanknoteIcon class="size-4" /> Tarjeta
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ formatCurrency(metrics.card_income) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <PackageIcon class="size-4" /> Productos vendidos
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ formatQuantity(metrics.products_sold) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <ScaleIcon class="size-4" /> Descuentos
                </div>
                <p class="mt-1 text-lg font-bold">
                    {{ formatCurrency(metrics.discount_total) }}
                </p>
            </div>
        </div>
    </div>
</template>
