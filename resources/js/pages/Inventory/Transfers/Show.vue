<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import TransferTimeline from '@/components/inventory/TransferTimeline.vue';
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
import {
    approve,
    cancel,
    index,
    receive,
    ship,
    submit as submitTransfer,
} from '@/routes/inventory/transfers';
import type { StockTransfer } from '@/types';

const props = defineProps<{
    transfer: StockTransfer;
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transferencias', href: index() },
            { title: 'Detalle de la transferencia', href: '#' },
        ],
    },
});

const receiveForm = useForm({
    received: Object.fromEntries(
        (props.transfer.items ?? []).map((item) => [item.id, '']),
    ) as Record<number, string>,
});

function transition(url: string) {
    router.post(url, {}, { preserveScroll: true });
}

function submitReceive() {
    receiveForm.post(receive(props.transfer.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Transferencia ${transfer.folio}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Transferencia ${transfer.folio}`"
            :description="`${transfer.origin_warehouse_name} → ${transfer.destination_warehouse_name}`"
        >
            <template #actions>
                <Badge variant="outline" class="text-sm">{{
                    transfer.status_label
                }}</Badge>
            </template>
        </PageHeader>

        <div v-if="can('inventory.transfer')" class="flex flex-wrap gap-2">
            <Button
                v-if="transfer.status === 'draft'"
                @click="transition(submitTransfer(transfer.id).url)"
            >
                Enviar a aprobación
            </Button>
            <Button
                v-if="transfer.status === 'pending'"
                @click="transition(approve(transfer.id).url)"
            >
                Aprobar
            </Button>
            <Button
                v-if="transfer.status === 'approved'"
                @click="transition(ship(transfer.id).url)"
            >
                Marcar en tránsito
            </Button>
            <ConfirmationDialog
                v-if="transfer.is_cancellable"
                title="¿Cancelar transferencia?"
                description="Esta acción no se puede deshacer."
                @confirm="transition(cancel(transfer.id).url)"
            >
                <template #trigger>
                    <Button variant="outline">Cancelar</Button>
                </template>
            </ConfirmationDialog>
        </div>

        <TransferTimeline :transfer="transfer" />

        <div class="overflow-x-auto rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Producto</TableHead>
                        <TableHead>Solicitado</TableHead>
                        <TableHead>Enviado</TableHead>
                        <TableHead>Recibido</TableHead>
                        <TableHead
                            v-if="
                                ['in_transit', 'partially_received'].includes(
                                    transfer.status,
                                ) && can('inventory.transfer')
                            "
                        >
                            Recibir ahora
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in transfer.items" :key="item.id">
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
                            formatQuantity(item.quantity_requested)
                        }}</TableCell>
                        <TableCell>{{
                            item.quantity_shipped !== null
                                ? formatQuantity(item.quantity_shipped)
                                : '—'
                        }}</TableCell>
                        <TableCell>{{
                            item.quantity_received !== null
                                ? formatQuantity(item.quantity_received)
                                : '—'
                        }}</TableCell>
                        <TableCell
                            v-if="
                                ['in_transit', 'partially_received'].includes(
                                    transfer.status,
                                ) && can('inventory.transfer')
                            "
                        >
                            <Input
                                v-model="receiveForm.received[item.id]"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="w-28"
                            />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Button
            v-if="
                ['in_transit', 'partially_received'].includes(
                    transfer.status,
                ) && can('inventory.transfer')
            "
            :disabled="receiveForm.processing"
            @click="submitReceive"
        >
            Registrar recepción
        </Button>

        <p v-if="transfer.notes" class="text-sm text-muted-foreground">
            Notas: {{ transfer.notes }}
        </p>
    </div>
</template>
