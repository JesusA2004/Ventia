<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DownloadIcon, FileTextIcon } from '@lucide/vue';
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
import { Bar, Line } from 'vue-chartjs';
import DateRangePicker from '@/components/filters/DateRangePicker.vue';
import ChartCard from '@/components/dashboard/ChartCard.vue';
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
import {
    exportMethod as exportReport,
    exportPdf as exportReportPdf,
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
    tab: 'summary' | 'sales' | 'cash' | 'inventory';
    filters: { date_from: string; date_to: string; branch_id: number | null };
    branchOptions: Branch[];
    canViewProfit: boolean;
    data: ReportData;
}>();

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
};

/** Builds a chart dataset straight from a report table by title, using the first column as labels and the last as values — avoids a second round-trip for data already on the page. */
function chartFromTable(title: string, color: string) {
    const table = props.data.tables.find((t) => t.title === title);

    if (!table || table.rows.length === 0) {
        return null;
    }

    const valueIndex = table.columns.length - 1;

    return {
        labels: table.rows.map((r) => String(r[0])),
        datasets: [
            {
                label: table.columns[valueIndex],
                data: table.rows.map((r) => Number(r[valueIndex])),
                backgroundColor: color,
                borderColor: color,
                fill: false,
                tension: 0.3,
            },
        ],
    };
}

const salesOverTimeChart = computed(() =>
    chartFromTable('Ventas por período', '#4f46e5'),
);
const cashMovementsChart = computed(() =>
    chartFromTable('Movimientos de caja por tipo', '#f59e0b'),
);
const inventoryValueChart = computed(() =>
    chartFromTable('Existencias valorizadas por almacén', '#22c55e'),
);

const tabs = [
    { value: 'summary', label: 'Resumen' },
    { value: 'sales', label: 'Ventas' },
    { value: 'cash', label: 'Caja' },
    { value: 'inventory', label: 'Inventario' },
];

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
</script>

<template>
    <Head title="Reportes" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Reportes"
            description="Ventas, caja e inventario del período y sucursal seleccionados."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <a
                        :href="
                            exportReport.url({
                                query: { tab, ...filters },
                            })
                        "
                    >
                        <DownloadIcon />
                        Exportar CSV
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

        <Tabs
            :model-value="tab"
            @update:model-value="(v) => apply({ tab: String(v) })"
        >
            <TabsList>
                <TabsTrigger v-for="t in tabs" :key="t.value" :value="t.value">
                    {{ t.label }}
                </TabsTrigger>
            </TabsList>
        </Tabs>

        <div class="flex flex-wrap items-end gap-3">
            <DateRangePicker
                :model-value="{
                    preset: 'custom',
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                }"
                @update:model-value="
                    (v) => apply({ date_from: v.date_from, date_to: v.date_to })
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
        </div>

        <div
            v-if="data.kpis.length > 0"
            class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        >
            <div
                v-for="kpi in data.kpis"
                :key="kpi.label"
                class="rounded-xl border p-4"
            >
                <p class="text-xs text-muted-foreground">{{ kpi.label }}</p>
                <p class="mt-1 text-xl font-bold">{{ kpi.value }}</p>
            </div>
        </div>

        <ChartCard
            v-if="tab === 'sales'"
            title="Ventas por período"
            :empty="!salesOverTimeChart"
        >
            <Line
                v-if="salesOverTimeChart"
                :data="salesOverTimeChart"
                :options="chartOptions"
            />
        </ChartCard>
        <ChartCard
            v-if="tab === 'cash'"
            title="Movimientos de caja por tipo"
            :empty="!cashMovementsChart"
        >
            <Bar
                v-if="cashMovementsChart"
                :data="cashMovementsChart"
                :options="chartOptions"
            />
        </ChartCard>
        <ChartCard
            v-if="tab === 'inventory'"
            title="Existencias valorizadas por almacén"
            :empty="!inventoryValueChart"
        >
            <Bar
                v-if="inventoryValueChart"
                :data="inventoryValueChart"
                :options="chartOptions"
            />
        </ChartCard>

        <div v-for="table in data.tables" :key="table.title" class="space-y-2">
            <h2 class="text-sm font-semibold">{{ table.title }}</h2>
            <div class="overflow-x-auto rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-for="col in table.columns" :key="col">
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
                            >
                                {{ cell }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </div>
</template>
