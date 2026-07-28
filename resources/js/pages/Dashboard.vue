<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BanknoteIcon,
    CreditCardIcon,
    PackageIcon,
    PercentIcon,
    ReceiptIcon,
    RotateCcwIcon,
    ScaleIcon,
    TicketIcon,
    TrendingDownIcon,
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
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    comparison: {
        sales_total: number | null;
        sales_count: number | null;
        ticket_average: number | null;
    };
    sales_over_time: { date: string; total: string; tickets: number }[];
    sales_by_branch: { name: string; total: string }[];
    payment_method_breakdown: { method: string; total: string }[];
    top_products: { name: string; quantity: string }[];
    top_categories: { name: string; total: string }[];
};

const props = defineProps<{
    metrics: Metrics;
    filters: {
        preset: string;
        date_from: string;
        date_to: string;
        branch_id: number | null;
        register_id: number | null;
        cashier_id: number | null;
    };
    branchOptions: Branch[];
    registerOptions: CashRegister[];
    cashierOptions: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const presets = [
    { value: 'today', label: 'Hoy' },
    { value: 'yesterday', label: 'Ayer' },
    { value: 'this_week', label: 'Esta semana' },
    { value: 'last_week', label: 'Semana anterior' },
    { value: 'this_month', label: 'Este mes' },
    { value: 'last_month', label: 'Mes anterior' },
];

function apply(partial: Record<string, string | number | undefined>) {
    router.get(
        dashboard().url,
        { ...props.filters, ...partial },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function setPreset(preset: string) {
    apply({ preset, date_from: undefined, date_to: undefined });
}

function setCustomRange(key: 'date_from' | 'date_to', value: string) {
    apply({ preset: 'custom', [key]: value });
}

const salesOverTimeChart = computed(() => ({
    labels: props.metrics.sales_over_time.map((d) => d.date.slice(5)),
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

const ticketAverageChart = computed(() => ({
    labels: props.metrics.sales_over_time.map((d) => d.date.slice(5)),
    datasets: [
        {
            label: 'Ticket promedio',
            data: props.metrics.sales_over_time.map((d) =>
                d.tickets > 0 ? Number(d.total) / d.tickets : 0,
            ),
            borderColor: '#22c55e',
            tension: 0.3,
        },
    ],
}));

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

const horizontalBarOptions = {
    ...chartOptions,
    indexAxis: 'y' as const,
};

function comparisonLabel(value: number | null): string | null {
    if (value === null) {
        return null;
    }

    const sign = value > 0 ? '+' : '';

    return `${sign}${value}% vs. período anterior`;
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <PageHeader
            title="Dashboard"
            description="Resumen de ventas, caja e inventario del período seleccionado."
        />

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-wrap gap-1">
                <Button
                    v-for="preset in presets"
                    :key="preset.value"
                    size="sm"
                    :variant="
                        filters.preset === preset.value ? 'default' : 'outline'
                    "
                    @click="setPreset(preset.value)"
                >
                    {{ preset.label }}
                </Button>
            </div>

            <div class="space-y-1.5">
                <Label for="date_from">Desde</Label>
                <Input
                    id="date_from"
                    type="date"
                    :model-value="filters.date_from"
                    @change="
                        (e: Event) =>
                            setCustomRange(
                                'date_from',
                                (e.target as HTMLInputElement).value,
                            )
                    "
                />
            </div>
            <div class="space-y-1.5">
                <Label for="date_to">Hasta</Label>
                <Input
                    id="date_to"
                    type="date"
                    :model-value="filters.date_to"
                    @change="
                        (e: Event) =>
                            setCustomRange(
                                'date_to',
                                (e.target as HTMLInputElement).value,
                            )
                    "
                />
            </div>

            <div class="w-48 space-y-1.5">
                <Label>Sucursal</Label>
                <Select
                    :model-value="
                        filters.branch_id ? String(filters.branch_id) : ''
                    "
                    @update:model-value="
                        (v) => apply({ branch_id: v ? String(v) : undefined })
                    "
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Todas" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todas</SelectItem>
                        <SelectItem
                            v-for="option in branchOptions"
                            :key="option.id"
                            :value="String(option.id)"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="w-48 space-y-1.5">
                <Label>Caja</Label>
                <Select
                    :model-value="
                        filters.register_id ? String(filters.register_id) : ''
                    "
                    @update:model-value="
                        (v) => apply({ register_id: v ? String(v) : undefined })
                    "
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Todas" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todas</SelectItem>
                        <SelectItem
                            v-for="option in registerOptions"
                            :key="option.id"
                            :value="String(option.id)"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="w-48 space-y-1.5">
                <Label>Cajero</Label>
                <Select
                    :model-value="
                        filters.cashier_id ? String(filters.cashier_id) : ''
                    "
                    @update:model-value="
                        (v) => apply({ cashier_id: v ? String(v) : undefined })
                    "
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Todos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">Todos</SelectItem>
                        <SelectItem
                            v-for="option in cashierOptions"
                            :key="option.id"
                            :value="String(option.id)"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <ReceiptIcon class="size-4" /> Ventas
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.sales_total) }}
                </p>
                <p
                    v-if="comparisonLabel(metrics.comparison.sales_total)"
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <component
                        :is="
                            (metrics.comparison.sales_total ?? 0) >= 0
                                ? TrendingUpIcon
                                : TrendingDownIcon
                        "
                        class="size-3"
                    />
                    {{ comparisonLabel(metrics.comparison.sales_total) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TicketIcon class="size-4" /> Tickets
                </div>
                <p class="mt-1 text-2xl font-bold">{{ metrics.sales_count }}</p>
                <p
                    v-if="comparisonLabel(metrics.comparison.sales_count)"
                    class="text-xs text-muted-foreground"
                >
                    {{ comparisonLabel(metrics.comparison.sales_count) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TicketIcon class="size-4" /> Ticket promedio
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.ticket_average) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <PercentIcon class="size-4" /> Descuentos
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.discount_total) }}
                </p>
            </div>
            <div
                v-if="metrics.profit_total !== null"
                class="rounded-xl border p-4"
            >
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TrendingUpIcon class="size-4" /> Utilidad
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.profit_total) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <BanknoteIcon class="size-4" /> Efectivo
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.cash_income) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <CreditCardIcon class="size-4" /> Tarjeta
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.card_income) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <PackageIcon class="size-4" /> Productos vendidos
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatQuantity(metrics.products_sold) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <ScaleIcon class="size-4" /> Efectivo esperado
                </div>
                <p class="mt-1 text-2xl font-bold">
                    {{ formatCurrency(metrics.expected_cash) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <ScaleIcon class="size-4" /> Diferencia de caja
                </div>
                <p
                    class="mt-1 text-2xl font-bold"
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
            <div class="rounded-xl border p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <WalletIcon class="size-4" /> Mi caja
                </div>
                <p
                    class="mt-1 text-2xl font-bold"
                    :class="
                        metrics.cash_session_open
                            ? 'text-emerald-600 dark:text-emerald-400'
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
                <p class="mb-2 text-sm font-medium">Ventas en el tiempo</p>
                <div
                    v-if="metrics.sales_over_time.length === 0"
                    class="flex h-64 items-center justify-center"
                >
                    <EmptyState title="Sin ventas en el período" />
                </div>
                <div v-else class="h-64">
                    <Line :data="salesOverTimeChart" :options="chartOptions" />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">
                    Ticket promedio en el tiempo
                </p>
                <div
                    v-if="metrics.sales_over_time.length === 0"
                    class="flex h-64 items-center justify-center"
                >
                    <EmptyState title="Sin ventas en el período" />
                </div>
                <div v-else class="h-64">
                    <Line :data="ticketAverageChart" :options="chartOptions" />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">Ventas por sucursal</p>
                <div
                    v-if="metrics.sales_by_branch.length === 0"
                    class="flex h-64 items-center justify-center"
                >
                    <EmptyState title="Sin ventas en el período" />
                </div>
                <div v-else class="h-64">
                    <Bar :data="branchChart" :options="chartOptions" />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">Métodos de pago</p>
                <div
                    v-if="metrics.payment_method_breakdown.length === 0"
                    class="flex h-64 items-center justify-center"
                >
                    <EmptyState title="Sin pagos en el período" />
                </div>
                <div v-else class="h-64">
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
                <p class="mb-2 text-sm font-medium">Productos más vendidos</p>
                <div
                    v-if="metrics.top_products.length === 0"
                    class="flex h-64 items-center justify-center"
                >
                    <EmptyState title="Sin ventas en el período" />
                </div>
                <div v-else class="h-64">
                    <Bar
                        :data="topProductsChart"
                        :options="horizontalBarOptions"
                    />
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <p class="mb-2 text-sm font-medium">Categorías más vendidas</p>
                <div
                    v-if="metrics.top_categories.length === 0"
                    class="flex h-64 items-center justify-center"
                >
                    <EmptyState title="Sin ventas en el período" />
                </div>
                <div v-else class="h-64">
                    <Bar :data="topCategoriesChart" :options="chartOptions" />
                </div>
            </div>
        </div>
    </div>
</template>
