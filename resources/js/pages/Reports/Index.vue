<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileSpreadsheetIcon, FileTextIcon, XIcon } from '@lucide/vue';
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
import { Badge } from '@/components/ui/badge';
import ChartCard from '@/components/dashboard/ChartCard.vue';
import DateRangePicker from '@/components/filters/DateRangePicker.vue';
import SearchableSelect from '@/components/forms/SearchableSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
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
import type { Branch } from '@/types';

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

const props = defineProps<{
    tab: ReportTab;
    tabs: { value: ReportTab; label: string }[];
    filters: { date_from: string; date_to: string; branch_id: number | null };
    branchOptions: Branch[];
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
};

const CHART_DEFS: Record<ReportTab, ChartDef[]> = {
    summary: [],
    sales: [
        {
            key: 'sales-trend',
            title: 'Tendencia de ventas',
            kind: 'line',
            tableTitle: 'Ventas por período',
        },
        {
            key: 'sales-branch',
            title: 'Ventas por sucursal',
            kind: 'bar',
            tableTitle: 'Ventas por sucursal',
        },
        {
            key: 'sales-payment',
            title: 'Métodos de pago',
            kind: 'doughnut',
            tableTitle: 'Ventas por método de pago',
        },
        {
            key: 'sales-products',
            title: 'Productos más vendidos (unidades)',
            kind: 'bar',
            tableTitle: 'Productos más vendidos',
            valueIndex: 1,
        },
    ],
    inventory: [
        {
            key: 'inventory-value',
            title: 'Existencias por almacén',
            kind: 'bar',
            tableTitle: 'Existencias valorizadas por almacén',
        },
    ],
    cash: [
        {
            key: 'cash-movements',
            title: 'Movimientos de caja por tipo',
            kind: 'bar',
            tableTitle: 'Movimientos de caja por tipo',
        },
    ],
    products: [
        {
            key: 'products-top',
            title: 'Productos más vendidos (ingresos)',
            kind: 'bar',
            tableTitle: 'Productos más vendidos',
            valueIndex: 2,
        },
        {
            key: 'products-category',
            title: 'Ventas por categoría',
            kind: 'doughnut',
            tableTitle: 'Ventas por categoría',
            valueIndex: 2,
        },
    ],
    customers: [
        {
            key: 'customers-top',
            title: 'Clientes con mayor compra',
            kind: 'bar',
            tableTitle: 'Clientes con mayor compra',
            valueIndex: 2,
        },
    ],
};

const charts = computed(() =>
    CHART_DEFS[props.tab].map((def) => ({
        ...def,
        data: chartFromTable(def.tableTitle, def.kind, def.valueIndex),
    })),
);

const selectedBranchName = computed(
    () =>
        props.branchOptions.find((b) => b.id === props.filters.branch_id)
            ?.name,
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reportes', href: reportsIndex() }],
    },
});

function apply(partial: Record<string, string | number | undefined>) {
    router.get(
        reportsIndex().url,
        { tab: props.tab, ...props.filters, ...partial },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearFilters() {
    apply({ branch_id: undefined, date_from: undefined, date_to: undefined });
}
</script>

<template>
    <Head title="Reportes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Reportes"
            description="Analiza ventas, inventario, caja y comportamiento operativo de tu negocio."
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
                    >
                        {{ t.label }}
                    </TabsTrigger>
                </TabsList>
            </Tabs>
            <p class="text-sm text-muted-foreground">
                {{ TAB_DESCRIPTIONS[tab] }}
            </p>

            <div class="flex flex-wrap items-end gap-3 border-t pt-4">
                <DateRangePicker
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
                <SearchableSelect
                    class="w-56"
                    :model-value="
                        filters.branch_id ? String(filters.branch_id) : null
                    "
                    :options="
                        branchOptions.map((b) => ({
                            value: String(b.id),
                            label: b.name,
                        }))
                    "
                    placeholder="Todas las sucursales"
                    all-label="Todas las sucursales"
                    @update:model-value="
                        (v) => apply({ branch_id: v ?? undefined })
                    "
                />
                <Button
                    v-if="filters.branch_id"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    Limpiar filtros
                </Button>
            </div>

            <div
                v-if="filters.branch_id"
                class="flex flex-wrap items-center gap-2"
            >
                <span class="text-xs text-muted-foreground">Filtros activos:</span>
                <Badge variant="secondary" class="gap-1">
                    Sucursal: {{ selectedBranchName }}
                    <button
                        type="button"
                        aria-label="Quitar filtro de sucursal"
                        @click="apply({ branch_id: undefined })"
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
