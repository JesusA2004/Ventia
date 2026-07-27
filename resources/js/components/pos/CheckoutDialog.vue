<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import sales from '@/routes/sales';
import { useCartStore } from '@/stores/cart';
import type { PaymentMethod, Sale } from '@/types';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{
    paymentMethodOptions: PaymentMethod[];
    registerId: number;
    warehouseId: number;
    cashSessionId: number | null;
}>();

const emit = defineEmits<{ completed: [sale: Sale] }>();

const cart = useCartStore();
const processing = ref(false);
const selectedMethodId = ref<number | null>(
    props.paymentMethodOptions[0]?.id ?? null,
);
const amount = ref('');
const reference = ref('');
const authorizationNumber = ref('');
const cardLastFour = ref('');

const selectedMethod = computed(
    () =>
        props.paymentMethodOptions.find(
            (m) => m.id === selectedMethodId.value,
        ) ?? null,
);

watch(open, (value) => {
    if (value) {
        cart.payments = [];
        amount.value = cart.paymentsPending.toFixed(2);
    }
});

function addPayment() {
    if (!selectedMethod.value) {
        return;
    }

    const value = Number(amount.value);

    if (!value || value <= 0) {
        toast.error('Captura un monto válido.');

        return;
    }

    cart.addPayment({
        payment_method_id: selectedMethod.value.id,
        payment_method_name: selectedMethod.value.name,
        amount: value.toFixed(4),
        reference: reference.value || undefined,
        authorization_number: authorizationNumber.value || undefined,
        card_last_four: cardLastFour.value || undefined,
    });

    reference.value = '';
    authorizationNumber.value = '';
    cardLastFour.value = '';
    amount.value = cart.paymentsPending.toFixed(2);
}

function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

async function submit() {
    if (processing.value || cart.paymentsPending > 0.0001) {
        return;
    }

    processing.value = true;

    try {
        const checkoutUuid = crypto.randomUUID();

        const response = await fetch(sales.store.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                register_id: props.registerId,
                warehouse_id: props.warehouseId,
                cash_session_id: props.cashSessionId,
                customer_id: cart.customer?.id,
                notes: cart.notes || null,
                checkout_uuid: checkoutUuid,
                general_discount: cart.generalDiscount,
                items: cart.lines.map((line) => ({
                    product_id: line.product_id,
                    product_variant_id: line.product_variant_id,
                    quantity: line.quantity,
                    discount_type: line.discount_type,
                    discount_value: line.discount_value,
                    notes: line.notes,
                })),
                payments: cart.payments.map((p) => ({
                    payment_method_id: p.payment_method_id,
                    amount: p.amount,
                    reference: p.reference,
                    authorization_number: p.authorization_number,
                    card_last_four: p.card_last_four,
                })),
            }),
        });

        if (!response.ok) {
            const error = await response.json().catch(() => null);
            const message = error?.errors
                ? Object.values(error.errors).flat().join(' ')
                : 'No se pudo completar la venta.';
            toast.error(message as string);

            return;
        }

        const payload = await response.json();
        toast.success(`Venta ${payload.data.folio} completada.`);
        open.value = false;
        emit('completed', payload.data);
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Cobrar venta</DialogTitle>
            </DialogHeader>

            <div class="grid grid-cols-2 gap-3 rounded-lg border p-3 text-sm">
                <div>
                    <p class="text-muted-foreground">Total</p>
                    <p class="text-lg font-bold">
                        ${{ cart.estimatedTotal.toFixed(2) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Pagado</p>
                    <p class="text-lg font-bold">
                        ${{ cart.paymentsTotal.toFixed(2) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Pendiente</p>
                    <p
                        class="text-lg font-bold"
                        :class="
                            cart.paymentsPending > 0
                                ? 'text-destructive'
                                : 'text-green-600'
                        "
                    >
                        ${{ cart.paymentsPending.toFixed(2) }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Cambio</p>
                    <p class="text-lg font-bold">
                        ${{ cart.paymentsChange.toFixed(2) }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <div class="grid grid-cols-3 gap-2">
                    <Select
                        :model-value="
                            selectedMethodId
                                ? String(selectedMethodId)
                                : undefined
                        "
                        @update:model-value="
                            (v) => (selectedMethodId = Number(v))
                        "
                    >
                        <SelectTrigger class="col-span-1">
                            <SelectValue placeholder="Método" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="method in paymentMethodOptions"
                                :key="method.id"
                                :value="String(method.id)"
                            >
                                {{ method.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        v-model="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="Monto"
                        class="col-span-1"
                    />
                    <Button type="button" class="col-span-1" @click="addPayment"
                        >Agregar pago</Button
                    >
                </div>

                <div
                    v-if="selectedMethod?.requires_reference"
                    class="grid grid-cols-3 gap-2"
                >
                    <Input v-model="reference" placeholder="Referencia" />
                    <Input
                        v-model="authorizationNumber"
                        placeholder="Núm. autorización"
                    />
                    <Input
                        v-model="cardLastFour"
                        placeholder="Últimos 4 dígitos"
                        maxlength="4"
                    />
                </div>
            </div>

            <ul
                v-if="cart.payments.length"
                class="divide-y rounded-md border text-sm"
            >
                <li
                    v-for="(payment, index) in cart.payments"
                    :key="index"
                    class="flex items-center justify-between px-3 py-2"
                >
                    <span>{{ payment.payment_method_name }}</span>
                    <span class="flex items-center gap-3">
                        ${{ Number(payment.amount).toFixed(2) }}
                        <button
                            type="button"
                            class="text-xs text-destructive"
                            @click="cart.removePayment(index)"
                        >
                            Quitar
                        </button>
                    </span>
                </li>
            </ul>

            <DialogFooter>
                <Button
                    :disabled="
                        processing ||
                        cart.paymentsPending > 0.0001 ||
                        !cart.payments.length
                    "
                    @click="submit"
                >
                    {{ processing ? 'Procesando...' : 'Confirmar cobro' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
