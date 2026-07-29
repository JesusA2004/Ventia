<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
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
import { usePermissions } from '@/composables/usePermissions';
import { formatQuantity } from '@/lib/format';
import { apply, cancel, complete, index } from '@/routes/inventory/counts';
import type { StockCount } from '@/types';

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

        <div class="overflow-x-auto rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Producto</TableHead>
                        <TableHead>Esperado</TableHead>
                        <TableHead>Contado</TableHead>
                        <TableHead>Diferencia</TableHead>
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
                        <TableCell>
                            <Badge
                                v-if="
                                    item.difference &&
                                    Number(item.difference) !== 0
                                "
                                :variant="
                                    Number(item.difference) > 0
                                        ? 'default'
                                        : 'destructive'
                                "
                            >
                                {{ formatQuantity(item.difference) }}
                            </Badge>
                            <span v-else>{{
                                item.difference !== null
                                    ? formatQuantity(item.difference)
                                    : '—'
                            }}</span>
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
