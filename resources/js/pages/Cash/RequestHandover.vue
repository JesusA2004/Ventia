<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DenominationCounter from '@/components/cash/DenominationCounter.vue';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/format';
import cash from '@/routes/cash';
import type { CashSession, CashSessionSummary } from '@/types';

const props = defineProps<{
    session: CashSession;
    summary: CashSessionSummary;
    denominationOptions: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: cash.sessions.index() },
            { title: 'Entrega de caja', href: '#' },
        ],
    },
});

const counter = ref<InstanceType<typeof DenominationCounter> | null>(null);

const form = useForm<{
    denominations: { denomination: number; quantity: number }[];
    cashier_notes: string;
}>({
    denominations: [],
    cashier_notes: '',
});

function submit() {
    form.denominations = counter.value?.lines ?? [];
    form.post(cash.sessions.handover.store.url(props.session.id));
}
</script>

<template>
    <Head title="Entrega de caja" />

    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <PageHeader
            title="Entrega de caja"
            :description="`Caja: ${session.register_name} — abierta ${formatDateTime(session.opened_at)}. Esta empresa requiere que un supervisor revise tu entrega antes de cerrar tu turno.`"
        />

        <p
            class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
        >
            Cuenta el efectivo físico en tu caja por denominación. El sistema
            comparará tu conteo contra lo esperado una vez que un supervisor
            revise la entrega — no verás el esperado en esta pantalla.
        </p>

        <DenominationCounter ref="counter" />

        <form class="space-y-4 rounded-lg border p-4" @submit.prevent="submit">
            <FormField
                label="Observaciones"
                for="cashier_notes"
                :error="form.errors.cashier_notes"
            >
                <Textarea
                    id="cashier_notes"
                    v-model="form.cashier_notes"
                    rows="2"
                    placeholder="Cualquier situación relevante durante tu turno..."
                />
            </FormField>
            <p
                v-if="form.errors.denominations"
                class="text-sm text-destructive"
            >
                {{ form.errors.denominations }}
            </p>
            <Button type="submit" size="lg" :disabled="form.processing">
                {{
                    form.processing
                        ? 'Enviando...'
                        : 'Enviar entrega para revisión'
                }}
            </Button>
        </form>
    </div>
</template>
