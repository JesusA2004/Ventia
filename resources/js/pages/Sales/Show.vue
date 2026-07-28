<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PrinterIcon } from '@lucide/vue';
import { reactive, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDateTime } from '@/lib/format';
import sales from '@/routes/sales';
import type { Sale } from '@/types';

const props = defineProps<{
    sale: Sale;
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Ventas', href: sales.index() },
            { title: 'Detalle de venta', href: '#' },
        ],
    },
});

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'outline',
    suspended: 'secondary',
    completed: 'default',
    partially_returned: 'secondary',
    returned: 'secondary',
    cancelled: 'destructive',
};

const cancelOpen = ref(false);
const cancelForm = useForm({ reason: '' });

function submitCancel() {
    cancelForm.post(sales.cancel.url(props.sale.id), {
        onSuccess: () => {
            cancelOpen.value = false;
        },
    });
}

const returnOpen = ref(false);
const returnReason = ref('');
const returnSelections = reactive<
    Record<number, { checked: boolean; quantity: string; restock: boolean }>
>(
    Object.fromEntries(
        (props.sale.items ?? []).map((item) => [
            item.id,
            { checked: false, quantity: item.quantity, restock: true },
        ]),
    ),
);
const returnForm = useForm({
    reason: '',
    items: [] as { sale_item_id: number; quantity: string; restock: boolean }[],
});

function submitReturn() {
    returnForm.reason = returnReason.value;
    returnForm.items = Object.entries(returnSelections)
        .filter(([, v]) => v.checked)
        .map(([id, v]) => ({
            sale_item_id: Number(id),
            quantity: v.quantity,
            restock: v.restock,
        }));

    returnForm.post(sales.returns.store.url(props.sale.id), {
        onSuccess: () => {
            returnOpen.value = false;
        },
    });
}

const canReturn = ['completed', 'partially_returned'].includes(
    props.sale.status,
);
</script>

<template>
    <Head :title="`Venta ${sale.folio}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Venta ${sale.folio}`"
            :description="
                sale.completed_at ? formatDateTime(sale.completed_at) : ''
            "
        >
            <template #actions>
                <div class="flex gap-2">
                    <Button as-child variant="outline">
                        <a
                            :href="sales.ticket.url(sale.id)"
                            target="_blank"
                            rel="noopener"
                        >
                            <PrinterIcon /> Ticket
                        </a>
                    </Button>

                    <Dialog
                        v-if="canReturn && can('sales.return')"
                        v-model:open="returnOpen"
                    >
                        <DialogTrigger as-child>
                            <Button variant="outline">Devolución</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Registrar devolución</DialogTitle>
                            </DialogHeader>
                            <form
                                class="space-y-4"
                                @submit.prevent="submitReturn"
                            >
                                <ul class="max-h-64 space-y-2 overflow-y-auto">
                                    <li
                                        v-for="item in sale.items"
                                        :key="item.id"
                                        class="flex items-center gap-2 rounded-md border p-2"
                                    >
                                        <Checkbox
                                            v-model="
                                                returnSelections[item.id]
                                                    .checked
                                            "
                                        />
                                        <span class="flex-1 text-sm">{{
                                            item.product_name
                                        }}</span>
                                        <Input
                                            v-model="
                                                returnSelections[item.id]
                                                    .quantity
                                            "
                                            type="number"
                                            min="0.0001"
                                            step="0.0001"
                                            class="h-8 w-20"
                                        />
                                    </li>
                                </ul>
                                <Input
                                    v-model="returnReason"
                                    placeholder="Motivo de la devolución"
                                    required
                                />
                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        :disabled="returnForm.processing"
                                        >Registrar devolución</Button
                                    >
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        v-if="
                            sale.status === 'completed' && can('sales.cancel')
                        "
                        v-model:open="cancelOpen"
                    >
                        <DialogTrigger as-child>
                            <Button variant="destructive"
                                >Cancelar venta</Button
                            >
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle
                                    >Cancelar venta
                                    {{ sale.folio }}</DialogTitle
                                >
                            </DialogHeader>
                            <form
                                class="space-y-4"
                                @submit.prevent="submitCancel"
                            >
                                <Input
                                    v-model="cancelForm.reason"
                                    placeholder="Motivo de la cancelación"
                                    required
                                />
                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        :disabled="cancelForm.processing"
                                        >Confirmar cancelación</Button
                                    >
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </template>
        </PageHeader>

        <div class="flex items-center gap-3">
            <Badge :variant="statusVariant[sale.status] ?? 'outline'">{{
                sale.status_label
            }}</Badge>
            <span
                v-if="sale.cancellation_reason"
                class="text-sm text-muted-foreground"
            >
                Motivo de cancelación: {{ sale.cancellation_reason }}
            </span>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="p-3">Producto</th>
                        <th class="p-3 text-right">Cantidad</th>
                        <th class="p-3 text-right">Precio</th>
                        <th class="p-3 text-right">Descuento</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in sale.items"
                        :key="item.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-3">
                            {{ item.product_name }}
                            <span
                                class="block font-mono text-xs text-muted-foreground"
                                >{{ item.sku }}</span
                            >
                        </td>
                        <td class="p-3 text-right">{{ item.quantity }}</td>
                        <td class="p-3 text-right">
                            {{ formatCurrency(item.unit_price) }}
                        </td>
                        <td class="p-3 text-right">
                            {{ formatCurrency(item.discount_amount) }}
                        </td>
                        <td class="p-3 text-right font-medium">
                            {{ formatCurrency(item.total) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1 rounded-lg border p-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Subtotal</span
                    ><span>{{ formatCurrency(sale.subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Descuento</span
                    ><span>-{{ formatCurrency(sale.discount_total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Impuestos</span
                    ><span>{{ formatCurrency(sale.tax_total) }}</span>
                </div>
                <div class="flex justify-between border-t pt-1 font-semibold">
                    <span>Total</span
                    ><span>{{ formatCurrency(sale.total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Recibido</span
                    ><span>{{ formatCurrency(sale.amount_received) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Cambio</span
                    ><span>{{ formatCurrency(sale.change_amount) }}</span>
                </div>
                <div
                    v-if="sale.profit_total !== undefined"
                    class="flex justify-between"
                >
                    <span class="text-muted-foreground">Utilidad</span
                    ><span>{{ formatCurrency(sale.profit_total) }}</span>
                </div>
            </div>
            <div class="space-y-2 rounded-lg border p-4 text-sm">
                <p class="font-medium">Pagos</p>
                <div
                    v-for="payment in sale.payments"
                    :key="payment.id"
                    class="flex justify-between"
                >
                    <span class="text-muted-foreground">{{
                        payment.payment_method_name
                    }}</span>
                    <span>{{ formatCurrency(payment.amount) }}</span>
                </div>
                <p class="pt-2 font-medium">Cliente</p>
                <p>{{ sale.customer_name }}</p>
                <p class="font-medium">Cajero</p>
                <p>{{ sale.cashier_name }}</p>
            </div>
        </div>
    </div>
</template>
