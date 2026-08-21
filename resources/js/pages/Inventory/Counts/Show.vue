<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ListIcon } from '@lucide/vue';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTime, formatQuantity } from '@/lib/format';
import { apply, cancel, complete, index } from '@/routes/inventory/counts';
import kardex from '@/routes/inventory/kardex';
import type { StockCount, StockCountItem } from '@/types';

const props = defineProps<{
    count: StockCount;
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Conteos físicos', href: index() },
            { title: 'Detalle del conteo', href: '#' },
        ],
    },
});

const completeForm = useForm({
    counted: Object.fromEntries(
        (props.count.items ?? []).map((item) => [
            item.id,
            item.counted_quantity ?? '',
        ]),
    ) as Record<number, string>,
});

function submitComplete() {
    completeForm.post(complete(props.count.id).url, { preserveScroll: true });
}

function applyCount() {
    router.post(apply(props.count.id).url, {}, { preserveScroll: true });
}

function cancelCount() {
    router.post(cancel(props.count.id).url, {}, { preserveScroll: true });
}

/** "+3" / "-2" / "0" — the sign itself is the primary signal, not just color. */
function formatDifference(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('es-MX', {
        signDisplay: 'exceptZero',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(value));
}

function differenceState(
    item: StockCountItem,
): 'pending' | 'match' | 'over' | 'short' {
    if (item.difference === null) {
        return 'pending';
    }

    const value = Number(item.difference);

    if (value === 0) {
        return 'match';
    }

    return value > 0 ? 'over' : 'short';
}

const stateLabels: Record<ReturnType<typeof differenceState>, string> = {
    pending: 'Sin contar',
    match: 'Sin diferencia',
    over: 'Sobrante',
    short: 'Faltante',
};

const stateVariants: Record<
    ReturnType<typeof differenceState>,
    'outline' | 'secondary' | 'default' | 'destructive'
> = {
    pending: 'outline',
    match: 'secondary',
    over: 'default',
    short: 'destructive',
};

/**
 * A count is only "applied" once per item with a real difference (see
 * ApplyStockCountAction), so the Kardex link — filtered to this product in
 * this warehouse, on the day the count was applied — only makes sense then.
 */
function kardexHref(item: StockCountItem): string {
    const day = props.count.applied_at?.slice(0, 10);

    return kardex.index.url({
        query: {
            warehouse_id: props.count.warehouse_id,
            product_id: item.product_id,
            ...(item.product_variant_id
                ? { product_variant_id: item.product_variant_id }
                : {}),
            ...(day ? { from: day, to: day } : {}),
        },
    });
}

function showsKardexLink(item: StockCountItem): boolean {
    return (
        can('inventory.kardex') &&
        props.count.status === 'applied' &&
        item.difference !== null &&
        Number(item.difference) !== 0
    );
}
</script>

<template>
    <Head :title="`Conteo ${count.folio}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Conteo ${count.folio}`"
            :description="count.warehouse_name"
        >
            <template #actions>
                <Badge variant="outline" class="text-sm">{{
                    count.status_label
                }}</Badge>
            </template>
        </PageHeader>

        <div v-if="can('inventory.count')" class="flex flex-wrap gap-2">
            <ConfirmationDialog
                v-if="count.status === 'completed'"
                title="¿Aplicar conteo?"
                description="Se generarán los ajustes de inventario para las diferencias encontradas. Esta acción no se puede deshacer."
                variant="default"
                @confirm="applyCount"
            >
                <template #trigger>
                    <Button>Aplicar diferencias</Button>
                </template>
            </ConfirmationDialog>
            <ConfirmationDialog
                v-if="['draft', 'counting'].includes(count.status)"
                title="¿Cancelar conteo?"
                description="Esta acción no se puede deshacer."
                @confirm="cancelCount"
            >
                <template #trigger>
                    <Button variant="outline">Cancelar</Button>
                </template>
            </ConfirmationDialog>
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-muted-foreground">
                Información general
            </p>
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Almacén</p>
                    <p class="font-medium">{{ count.warehouse_name }}</p>
                </div>
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Sucursal</p>
                    <p class="font-medium">{{ count.branch_name ?? '—' }}</p>
                </div>
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Productos incluidos</p>
                    <p class="font-medium">{{ count.items?.length ?? 0 }}</p>
                </div>
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Estado</p>
                    <p class="font-medium">{{ count.status_label }}</p>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-muted-foreground">
                Historial
            </p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Iniciado</p>
                    <p class="font-medium">
                        {{ formatDateTime(count.started_at) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ count.started_by_name ?? '—' }}
                    </p>
                </div>
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Completado</p>
                    <p class="font-medium">
                        {{ formatDateTime(count.completed_at) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ count.completed_by_name ?? '—' }}
                    </p>
                </div>
                <div class="rounded-lg border p-3 text-sm">
                    <p class="text-muted-foreground">Aplicado</p>
                    <p class="font-medium">
                        {{ formatDateTime(count.applied_at) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ count.applied_by_name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Producto</TableHead>
                        <TableHead>Esperado</TableHead>
                        <TableHead>Contado</TableHead>
                        <TableHead>Diferencia</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead class="text-right">Kardex</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in count.items" :key="item.id">
                        <TableCell>
                            <div class="flex flex-col">
                                <span>{{ item.product_name }}</span>
                                <span class="text-xs text-muted-foreground">
                                    SKU {{ item.product_sku
                                    }}<span v-if="item.variant_label">
                                        · {{ item.variant_label }}</span
                                    >
                                </span>
                            </div>
                        </TableCell>
                        <TableCell>{{
                            formatQuantity(item.expected_quantity)
                        }}</TableCell>
                        <TableCell>
                            <Input
                                v-if="
                                    count.status === 'counting' &&
                                    can('inventory.count')
                                "
                                v-model="completeForm.counted[item.id]"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="w-28"
                            />
                            <span v-else>{{
                                item.counted_quantity !== null
                                    ? formatQuantity(item.counted_quantity)
                                    : '—'
                            }}</span>
                        </TableCell>
                        <TableCell class="font-medium">
                            {{ formatDifference(item.difference) }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="stateVariants[differenceState(item)]"
                            >
                                {{ stateLabels[differenceState(item)] }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Tooltip v-if="showsKardexLink(item)">
                                <TooltipTrigger as-child>
                                    <Button
                                        as-child
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Ver en Kardex"
                                    >
                                        <Link :href="kardexHref(item)">
                                            <ListIcon />
                                        </Link>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent
                                    >Ver movimientos en Kardex</TooltipContent
                                >
                            </Tooltip>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Button
            v-if="count.status === 'counting' && can('inventory.count')"
            :disabled="completeForm.processing"
            @click="submitComplete"
        >
            Completar conteo
        </Button>

        <p v-if="count.notes" class="text-sm text-muted-foreground">
            Notas: {{ count.notes }}
        </p>
    </div>
</template>
