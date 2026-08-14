<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BoxesIcon,
    FileSpreadsheetIcon,
    FileTextIcon,
    LayoutDashboardIcon,
    Loader2Icon,
    ShoppingCartIcon,
    TagIcon,
    UsersIcon,
    WalletIcon,
    XIcon,
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
import { computed, ref } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import { Badge } from '@/components/ui/badge';
import ChartCard from '@/components/dashboard/ChartCard.vue';
import DateRangePicker from '@/components/filters/DateRangePicker.vue';
import SearchableSelect from '@/components/forms/SearchableSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatReportValue, isNumericLabel } from '@/lib/reportFormat';
import {
    exportPdf as exportReportPdf,
    exportXlsx as exportReportXlsx,
    index as reportsIndex,
} from '@/routes/reports';

type ReportTab =
    | 'summary'
    | 'sales'
    | 'inventory'
    | 'cash'
    | 'products'
    | 'customers';

type ReportTable = {
    title: string;
    columns: string[];
    rows: (string | number | null)[][];
};

type ReportData = {
    kpis: { label: string; value: string }[];
    tables: ReportTable[];
};

type NamedOption = { id: number; name: string };

type FilterKey =
    | 'register_id'
    | 'cashier_id'
    | 'category_id'
    | 'payment_method_id'
    | 'product_id'
    | 'customer_id';

type GroupBy = 'day' | 'week' | 'month';

type ReportFilters = {
    date_from: string;
    date_to: string;
    group_by: GroupBy;
    branch_id: number | null;
    register_id: number | null;
    cashier_id: number | null;
    category_id: number | null;
    payment_method_id: number | null;
    product_id: number | null;
    customer_id: number | null;
};

const props = defineProps<{
    tab: ReportTab;
    tabs: { value: ReportTab; label: string }[];
    filters: ReportFilters;
    branchOptions: NamedOption[];
    registerOptions: NamedOption[];
    cashierOptions: NamedOption[];
    categoryOptions: NamedOption[];
    paymentMethodOptions: NamedOption[];
    productOptions: NamedOption[];
    customerOptions: NamedOption[];
    canViewProfit: boolean;
    data: ReportData;
}>();

const TAB_DESCRIPTIONS: Record<ReportTab, string> = {
    summary:
        'Panorama general del período: ventas, tickets, descuentos, cancelaciones, devoluciones y diferencias de caja.',
    sales: 'Tendencia de ventas, desempeño por sucursal, por cajero, por método de pago y productos más vendidos.',
    inventory:
        'Existencias valorizadas por almacén, productos bajo mínimo, sin existencias y movimientos por tipo.',
    cash: 'Sesiones con diferencias de efectivo y movimientos de caja agrupados por tipo.',
    products:
        'Desempeño por producto: más y menos vendidos, ventas por categoría y productos sin movimiento.',
    customers:
        'Clientes nuevos, clientes con mayor compra y uso de crédito en el período.',
};

const TAB_ICONS: Record<ReportTab, typeof LayoutDashboardIcon> = {
    summary: LayoutDashboardIcon,
    sales: ShoppingCartIcon,
    inventory: BoxesIcon,
    cash: WalletIcon,
    products: TagIcon,
    customers: UsersIcon,
};

/** Inventario is a point-in-time balance, not a period — no date range there. See InventoryReportService. */
const TABS_WITHOUT_DATE_RANGE: ReportTab[] = ['inventory'];

/** Which of the shared entity filters make sense on each tab — never show a control the backend ignores. */
const TAB_FILTERS: Record<ReportTab, FilterKey[]> = {
    summary: [],
    sales: [
        'register_id',
        'cashier_id',
        'category_id',
        'product_id',
        'payment_method_id',
    ],
    inventory: ['category_id', 'product_id'],
    cash: ['register_id', 'cashier_id'],
    products: ['category_id', 'product_id'],
    customers: ['customer_id'],
};

type FilterMeta = {
    label: string;
    allLabel: string;
    options: () => NamedOption[];
};

const FILTER_META: Record<FilterKey, FilterMeta> = {
    register_id: {
        label: 'Caja',
        allLabel: 'Todas las cajas',
        options: () => props.registerOptions,
    },
    cashier_id: {
        label: 'Cajero',
        allLabel: 'Todos los cajeros',
        options: () => props.cashierOptions,
    },
    category_id: {
        label: 'Categoría',
        allLabel: 'Todas las categorías',
        options: () => props.categoryOptions,
    },
    payment_method_id: {
        label: 'Método de pago',
        allLabel: 'Todos los métodos',
        options: () => props.paymentMethodOptions,
    },
    product_id: {
        label: 'Producto',
        allLabel: 'Todos los productos',
        options: () => props.productOptions,
    },
    customer_id: {
        label: 'Cliente',
        allLabel: 'Todos los clientes',
        options: () => props.customerOptions,
    },
};

const visibleFilterKeys = computed(() => TAB_FILTERS[props.tab]);
const showDateRange = computed(
    () => !TABS_WITHOUT_DATE_RANGE.includes(props.tab),
);
const showGrouping = computed(() => props.tab === 'sales');

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

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: true, position: 'bottom' as const } },
};

const PALETTE = [
    '#4f46e5',
    '#8b5cf6',
    '#f59e0b',
    '#22c55e',
    '#ef4444',
    '#06b6d4',
    '#ec4899',
    '#84cc16',
];

/** Builds a chart dataset straight from a report table by title, using the first column as labels and the last column (or an explicit index) as values — avoids a second round-trip for data already on the page. */
function chartFromTable(
    title: string,
    kind: 'line' | 'bar' | 'doughnut',
    columnIndex?: number,
) {
    const table = props.data.tables.find((t) => t.title === title);

    if (!table || table.rows.length === 0) {
        return null;
    }

    const valueIndex = columnIndex ?? table.columns.length - 1;
    const labels = table.rows.map((r) => String(r[0]));
    const values = table.rows.map((r) => Number(r[valueIndex]));

    if (kind === 'doughnut') {
        return {
            labels,
            datasets: [
                {
                    label: table.columns[valueIndex],
                    data: values,
                    backgroundColor: labels.map(
                        (_, i) => PALETTE[i % PALETTE.length],
                    ),
                },
            ],
        };
    }

    const color = PALETTE[0];

    return {
        labels,
        datasets: [
            {
                label: table.columns[valueIndex],
                data: values,
                backgroundColor: kind === 'bar' ? color : 'transparent',
                borderColor: color,
                fill: kind === 'line' ? 'origin' : false,
                tension: 0.3,
            },
        ],
    };
}

type ChartDef = {
    key: string;
    title: string;
    kind: 'line' | 'bar' | 'doughnut';
    tableTitle: string;
    valueIndex?: number;
    emptyMessage: string;
};

const CHART_DEFS: Record<ReportTab, ChartDef[]> = {
    summary: [
        {
            key: 'summary-trend',
            title: 'Tendencia de ventas',
            kind: 'line',
            tableTitle: 'Ventas por período',
            emptyMessage: 'No hay ventas en el período seleccionado.',
        },
        {
            key: 'summary-branch',
            title: 'Ventas por sucursal',
            kind: 'bar',
            tableTitle: 'Ventas por sucursal',
            emptyMessage: 'No hay ventas por sucursal en el período seleccionado.',
        },
        {
            key: 'summary-payment',
            title: 'Métodos de pago',
            kind: 'doughnut',
            tableTitle: 'Ventas por método de pago',
            emptyMessage: 'No hay cobros registrados en el período seleccionado.',
        },
    ],
    sales: [
        {
            key: 'sales-trend',
            title: 'Tendencia de ventas',
            kind: 'line',
            tableTitle: 'Ventas por período',
            emptyMessage: 'No hay ventas en el período seleccionado.',
        },
        {
            key: 'sales-branch',
            title: 'Ventas por sucursal',
            kind: 'bar',
            tableTitle: 'Ventas por sucursal',
            emptyMessage: 'No hay ventas por sucursal en el período seleccionado.',
        },
        {
            key: 'sales-payment',
            title: 'Métodos de pago',
            kind: 'doughnut',
            tableTitle: 'Ventas por método de pago',
            emptyMessage: 'No hay cobros registrados en el período seleccionado.',
        },
        {
            key: 'sales-products',
            title: 'Productos más vendidos (unidades)',
            kind: 'bar',
            tableTitle: 'Productos más vendidos',
            valueIndex: 1,
            emptyMessage: 'No hay productos vendidos en el período seleccionado.',
        },
    ],
    inventory: [
        {
            key: 'inventory-value',
            title: 'Existencias por almacén',
            kind: 'bar',
            tableTitle: 'Existencias valorizadas por almacén',
            emptyMessage: 'No hay existencias registradas para estos filtros.',
        },
    ],
    cash: [
        {
            key: 'cash-movements',
            title: 'Movimientos de caja por tipo',
            kind: 'bar',
            tableTitle: 'Movimientos de caja por tipo',
            emptyMessage: 'No hay movimientos de caja en el período seleccionado.',
        },
    ],
    products: [
        {
            key: 'products-top',
            title: 'Productos más vendidos (ingresos)',
            kind: 'bar',
            tableTitle: 'Productos más vendidos',
            valueIndex: 2,
            emptyMessage: 'No hay productos vendidos en el período seleccionado.',
        },
        {
            key: 'products-category',
            title: 'Ventas por categoría',
            kind: 'doughnut',
            tableTitle: 'Ventas por categoría',
            valueIndex: 2,
            emptyMessage: 'No hay ventas por categoría en el período seleccionado.',
        },
    ],
    customers: [
        {
            key: 'customers-top',
            title: 'Clientes con mayor compra',
            kind: 'bar',
            tableTitle: 'Clientes con mayor compra',
            valueIndex: 2,
            emptyMessage: 'No hay compras de clientes en el período seleccionado.',
        },
    ],
};

const charts = computed(() =>
    CHART_DEFS[props.tab].map((def) => ({
        ...def,
        data: chartFromTable(def.tableTitle, def.kind, def.valueIndex),
    })),
);

function optionName(options: NamedOption[], id: number | null) {
    return options.find((o) => o.id === id)?.name;
}

const activeFilterBadges = computed(() => {
    const badges: { key: string; label: string; onClear: () => void }[] = [];

    if (props.filters.branch_id) {
        const name = optionName(props.branchOptions, props.filters.branch_id);
        if (name) {
            badges.push({
                key: 'branch_id',
                label: `Sucursal: ${name}`,
                onClear: () => apply({ branch_id: undefined }),
            });
        }
    }

    for (const key of visibleFilterKeys.value) {
        const value = props.filters[key];
        if (!value) continue;
        const meta = FILTER_META[key];
        const name = optionName(meta.options(), value);
        if (!name) continue;
        badges.push({
            key,
            label: `${meta.label}: ${name}`,
            onClear: () => apply({ [key]: undefined }),
        });
    }

    return badges;
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reportes', href: reportsIndex() }],
    },
});

const updating = ref(false);

function apply(partial: Record<string, string | number | undefined>) {
    router.get(
        reportsIndex().url,
        { tab: props.tab, ...props.filters, ...partial },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (updating.value = true),
            onFinish: () => (updating.value = false),
        },
    );
}

function clearFilters() {
    apply({
        branch_id: undefined,
        register_id: undefined,
        cashier_id: undefined,
        category_id: undefined,
        payment_method_id: undefined,
        product_id: undefined,
        customer_id: undefined,
    });
}

const hasActiveFilters = computed(() => activeFilterBadges.value.length > 0);
</script>

<template>
    <Head title="Reportes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Reportes"
            description="Centro analítico: qué ocurrió, cuándo, dónde y con qué producto o cajero, a lo largo del período seleccionado."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <a
                        :href="
                            exportReportXlsx.url({
                                query: { tab, ...filters },
                            })
                        "
                    >
                        <FileSpreadsheetIcon />
                        Exportar Excel
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="
                            exportReportPdf.url({
                                query: { tab, ...filters },
                            })
                        "
                        target="_blank"
                        rel="noopener"
                    >
                        <FileTextIcon />
                        Exportar PDF
                    </a>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-4 rounded-xl border bg-card p-4">
            <Tabs
                :model-value="tab"
                @update:model-value="(v) => apply({ tab: String(v) })"
            >
                <TabsList class="flex-wrap">
                    <TabsTrigger
                        v-for="t in tabs"
                        :key="t.value"
                        :value="t.value"
                        class="gap-1.5"
                    >
                        <component :is="TAB_ICONS[t.value]" class="size-4" />
                        {{ t.label }}
                    </TabsTrigger>
                </TabsList>
            </Tabs>
            <div class="flex items-center gap-2">
                <p class="text-sm text-muted-foreground">
                    {{ TAB_DESCRIPTIONS[tab] }}
                </p>
                <span
                    v-if="updating"
                    class="flex items-center gap-1 text-xs text-muted-foreground"
                >
                    <Loader2Icon class="size-3 animate-spin" />
                    Actualizando…
                </span>
            </div>

            <div class="flex flex-wrap items-end gap-3 border-t pt-4">
                <DateRangePicker
                    v-if="showDateRange"
                    :model-value="{
                        preset: 'custom',
                        date_from: filters.date_from,
                        date_to: filters.date_to,
                    }"
                    @update:model-value="
                        (v) =>
                            apply({ date_from: v.date_from, date_to: v.date_to })
                    "
                />
                <div v-if="showGrouping" class="flex flex-col gap-1">
                    <Select
                        :model-value="filters.group_by"
                        @update:model-value="
                            (v) => apply({ group_by: String(v) })
                        "
                    >
                        <SelectTrigger class="w-40">
                            <SelectValue placeholder="Agrupación" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="day">Diaria</SelectItem>
                            <SelectItem value="week">Semanal</SelectItem>
                            <SelectItem value="month">Mensual</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <SearchableSelect
                    class="w-48"
                    :model-value="
                        filters.branch_id ? String(filters.branch_id) : null
                    "
                    :options="
                        branchOptions.map((b) => ({
                            value: String(b.id),
                            label: b.name,
                        }))
                    "
                    placeholder="Sucursal"
                    all-label="Todas las sucursales"
                    @update:model-value="
                        (v) => apply({ branch_id: v ?? undefined })
                    "
                />
                <SearchableSelect
                    v-for="key in visibleFilterKeys"
                    :key="key"
                    class="w-48"
                    :model-value="filters[key] ? String(filters[key]) : null"
                    :options="
                        FILTER_META[key]
                            .options()
                            .map((o) => ({ value: String(o.id), label: o.name }))
                    "
                    :placeholder="FILTER_META[key].label"
                    :all-label="FILTER_META[key].allLabel"
                    @update:model-value="
                        (v) => apply({ [key]: v ?? undefined })
                    "
                />
                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    Limpiar filtros
                </Button>
            </div>

            <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-muted-foreground">Filtros activos:</span>
                <Badge
                    v-for="badge in activeFilterBadges"
                    :key="badge.key"
                    variant="secondary"
                    class="gap-1"
                >
                    {{ badge.label }}
                    <button
                        type="button"
                        :aria-label="`Quitar filtro: ${badge.label}`"
                        @click="badge.onClear"
                    >
                        <XIcon class="size-3" />
                    </button>
                </Badge>
            </div>
        </div>

        <div
            v-if="data.kpis.length > 0"
            class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        >
            <div
                v-for="kpi in data.kpis"
                :key="kpi.label"
                class="rounded-xl border bg-card p-4"
            >
                <p class="text-xs text-muted-foreground">{{ kpi.label }}</p>
                <p class="mt-1 text-2xl font-bold tracking-tight">
                    {{ formatReportValue(kpi.label, kpi.value) }}
                </p>
            </div>
        </div>

        <div
            v-if="charts.length > 0"
            class="grid grid-cols-1 gap-4"
            :class="charts.length > 1 ? 'lg:grid-cols-2' : ''"
        >
            <ChartCard
                v-for="chart in charts"
                :key="chart.key"
                :title="chart.title"
                :empty="!chart.data"
                :height="chart.kind === 'line' ? 'h-72' : 'h-64'"
            >
                <template #empty>
                    <p class="text-sm text-muted-foreground">
                        {{ chart.emptyMessage }}
                    </p>
                </template>
                <Line
                    v-if="chart.data && chart.kind === 'line'"
                    :data="chart.data"
                    :options="chartOptions"
                />
                <Bar
                    v-else-if="chart.data && chart.kind === 'bar'"
                    :data="chart.data"
                    :options="chartOptions"
                />
                <Doughnut
                    v-else-if="chart.data && chart.kind === 'doughnut'"
                    :data="chart.data"
                    :options="doughnutOptions"
                />
            </ChartCard>
        </div>

        <div
            v-for="table in data.tables"
            :key="table.title"
            class="space-y-2 rounded-xl border bg-card p-4"
        >
            <h2 class="text-sm font-semibold">{{ table.title }}</h2>
            <div class="max-h-96 overflow-auto rounded-lg border">
                <Table>
                    <TableHeader class="sticky top-0 z-10 bg-card">
                        <TableRow>
                            <TableHead
                                v-for="col in table.columns"
                                :key="col"
                                :class="
                                    isNumericLabel(col) ? 'text-right' : ''
                                "
                            >
                                {{ col }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="table.rows.length === 0">
                            <TableCell
                                :colspan="table.columns.length"
                                class="text-center text-sm text-muted-foreground"
                            >
                                Sin datos en el período seleccionado.
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="(row, index) in table.rows"
                            :key="index"
                        >
                            <TableCell
                                v-for="(cell, cellIndex) in row"
                                :key="cellIndex"
                                :class="
                                    isNumericLabel(table.columns[cellIndex])
                                        ? 'text-right tabular-nums'
                                        : ''
                                "
                            >
                                {{
                                    formatReportValue(
                                        table.columns[cellIndex],
                                        cell,
                                    )
                                }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>
</template>
