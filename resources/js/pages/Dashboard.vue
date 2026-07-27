<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BanknoteIcon,
    CreditCardIcon,
    PackageIcon,
    ReceiptIcon,
    RotateCcwIcon,
    TicketIcon,
    TrendingUpIcon,
    WalletIcon,
    XCircleIcon,
} from '@lucide/vue';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip as ChartTooltip,
    ArcElement,
} from 'chart.js';
import { computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { dashboard } from '@/routes';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    ChartTooltip,
    Legend,
);

type Metrics = {
    sales_count: number;
    sales_total: string;
    ticket_average: string;
    cash_income: string;
    card_income: string;
    products_sold: string;
    cash_session_open: boolean;
    low_stock_count: number;
    cancelled_count: number;
    returns_count: number;
    sales_by_hour: { hour: number; total: string }[];
    sales_last_7_days: { date: string; total: string }[];
    payment_method_breakdown: { method: string; total: string }[];
    top_products: { name: string; quantity: string }[];
    top_categories: { name: string; total: string }[];
};

const props = defineProps<{ metrics: Metrics }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const hourlyChart = computed(() => ({
    labels: props.metrics.sales_by_hour.map((h) => `${h.hour}h`),
    datasets: [
        {
            label: 'Ventas',
            data: props.metrics.sales_by_hour.map((h) => Number(h.total)),
            backgroundColor: '#4f46e5',
        },
    ],
}));

const weeklyChart = computed(() => ({
    labels: props.metrics.sales_last_7_days.map((d) => d.date.slice(5)),
    datasets: [
        {
            label: 'Ventas',
            data: props.metrics.sales_last_7_days.map((d) => Number(d.total)),
            borderColor: '#4f46e5',
            tension: 0.3,
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
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <ReceiptIcon class="size-4" /> Ventas de hoy
                </div>
                <p class="mt-1 text-2xl font-bold">
                    ${{ metrics.sales_total }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ metrics.sales_count }} tickets
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TicketIcon class="size-4" /> Ticket promedio
                </div>
                <p class="mt-1 text-2xl font-bold">
                    ${{ metrics.ticket_average }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <BanknoteIcon class="size-4" /> Efectivo
                </div>
                <p class="mt-1 text-2xl font-bold">
                    ${{ metrics.cash_income }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <CreditCardIcon class="size-4" /> Tarjeta
                </div>
                <p class="mt-1 text-2xl font-bold">
                    ${{ metrics.card_income }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <PackageIcon class="size-4" /> Productos vendidos
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ metrics.products_sold }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <WalletIcon class="size-4" /> Caja
                </div>
                <p
                    class="mt-1 text-2xl font-bold"
                    :class="
                        metrics.cash_session_open
                            ? 'text-green-600'
                            : 'text-muted-foreground'
                    "
                >
                    {{ metrics.cash_session_open ? 'Abierta' : 'Cerrada' }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TrendingUpIcon class="size-4" /> Stock bajo
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ metrics.low_stock_count }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <XCircleIcon class="size-4" /> Canceladas
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ metrics.cancelled_count }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <RotateCcwIcon class="size-4" /> Devoluciones
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ metrics.returns_count }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">Ventas por hora del día</p>
                <div class="h-64">
                    <Bar :data="hourlyChart" :options="chartOptions" />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">
                    Ventas de los últimos 7 días
                </p>
                <div class="h-64">
                    <Line :data="weeklyChart" :options="chartOptions" />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">Métodos de pago (7 días)</p>
                <div class="h-64">
                    <Doughnut
                        :data="paymentChart"
                        :options="{
                            responsive: true,
                            maintainAspectRatio: false,
                        }"
                    />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">
                    Productos más vendidos (7 días)
                </p>
                <div class="h-64">
                    <Bar :data="topProductsChart" :options="chartOptions" />
                </div>
            </div>
            <div class="rounded-xl border p-4 lg:col-span-2">
                <p class="mb-2 text-sm font-medium">
                    Categorías más vendidas (7 días)
                </p>
                <div class="h-64">
                    <Bar :data="topCategoriesChart" :options="chartOptions" />
                </div>
            </div>
        </div>
    </div>
</template>
