<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DenominationCounter from '@/components/cash/DenominationCounter.vue';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency, formatDateTime } from '@/lib/format';
import cash from '@/routes/cash';
import type { CashSession, CashSessionSummary } from '@/types';

const props = defineProps<{
    session: CashSession;
    summary: CashSessionSummary;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: cash.sessions.index() },
            { title: 'Cerrar caja', href: '#' },
        ],
    },
});

const counter = ref<InstanceType<typeof DenominationCounter> | null>(null);
const useCounter = ref(false);

const form = useForm({
    counted_cash: props.summary.expected_cash,
    closing_notes: '',
});

function useCounterTotal() {
    if (counter.value) {
        form.counted_cash = counter.value.total.toFixed(2);
    }
}

function submit() {
    form.post(cash.sessions.close.url(props.session.id));
}
</script>

<template>
    <Head title="Cerrar caja" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <PageHeader
            title="Cierre y arqueo de caja"
            :description="`Caja: ${session.register_name} — abierta ${formatDateTime(session.opened_at)}`"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2 rounded-lg border p-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Fondo inicial</span
                    ><span>{{ formatCurrency(summary.opening_amount) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Ventas en efectivo</span
                    ><span>{{ formatCurrency(summary.cash_sales) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Depósitos</span
                    ><span>{{ formatCurrency(summary.deposits) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Retiros</span
                    ><span>-{{ formatCurrency(summary.withdrawals) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Gastos</span
                    ><span>-{{ formatCurrency(summary.expenses) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Reembolsos</span
                    ><span>-{{ formatCurrency(summary.refunds) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Ajustes</span
                    ><span>{{ formatCurrency(summary.adjustments) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2 font-semibold">
                    <span>Efectivo esperado</span
                    ><span>{{ formatCurrency(summary.expected_cash) }}</span>
                </div>
            </div>
            <div class="space-y-2 rounded-lg border p-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Pagos con tarjeta</span
                    ><span>{{ formatCurrency(summary.card_total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Transferencias</span
                    ><span>{{ formatCurrency(summary.transfer_total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Otros métodos</span
                    ><span>{{ formatCurrency(summary.other_total) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2 font-semibold">
                    <span>Total general del turno</span
                    ><span>{{ formatCurrency(summary.grand_total) }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="useCounter = !useCounter"
            >
                {{
                    useCounter
                        ? 'Ocultar conteo por denominaciones'
                        : 'Contar por denominaciones'
                }}
            </Button>
        </div>

        <DenominationCounter v-if="useCounter" ref="counter" />
        <Button
            v-if="useCounter"
            type="button"
            variant="secondary"
            size="sm"
            @click="useCounterTotal"
        >
            Usar este total
        </Button>

        <form class="space-y-4 rounded-lg border p-4" @submit.prevent="submit">
            <FormField
                label="Efectivo contado"
                for="counted_cash"
                required
                :error="form.errors.counted_cash"
            >
                <Input
                    id="counted_cash"
                    v-model="form.counted_cash"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                />
            </FormField>
            <p class="text-sm text-muted-foreground">
                Diferencia estimada:
                <span
                    :class="
                        Number(form.counted_cash) -
                            Number(summary.expected_cash) <
                        0
                            ? 'text-destructive'
                            : 'text-green-600'
                    "
                >
                    {{
                        formatCurrency(
                            Number(form.counted_cash) -
                                Number(summary.expected_cash),
                        )
                    }}
                </span>
            </p>
            <FormField
                label="Observaciones"
                for="closing_notes"
                :error="form.errors.closing_notes"
            >
                <Textarea
                    id="closing_notes"
                    v-model="form.closing_notes"
                    rows="2"
                />
            </FormField>
            <Button type="submit" size="lg" :disabled="form.processing">
                {{ form.processing ? 'Cerrando caja...' : 'Cerrar caja' }}
            </Button>
        </form>
    </div>
</template>
