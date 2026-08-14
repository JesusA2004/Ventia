<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDateTime } from '@/lib/format';
import cash from '@/routes/cash';
import type { CashHandover } from '@/types';

const props = defineProps<{
    handover: CashHandover;
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: cash.sessions.index() },
            { title: 'Entregas de caja', href: cash.handovers.index() },
            { title: 'Detalle de entrega', href: '#' },
        ],
    },
});

const dialogOpen = ref(false);
const activeDecision = ref<'approve' | 'reject' | 'recount'>('approve');

const decisionCopy: Record<
    'approve' | 'reject' | 'recount',
    { title: string; confirmLabel: string }
> = {
    approve: {
        title: 'Aprobar entrega de caja',
        confirmLabel: 'Aprobar y cerrar caja',
    },
    reject: { title: 'Rechazar entrega de caja', confirmLabel: 'Rechazar' },
    recount: {
        title: 'Solicitar reconteo',
        confirmLabel: 'Solicitar reconteo',
    },
};

const form = useForm<{
    decision: 'approve' | 'reject' | 'recount';
    supervisor_email: string;
    supervisor_password: string;
    notes: string;
}>({
    decision: 'approve',
    supervisor_email: '',
    supervisor_password: '',
    notes: '',
});

function openDialog(decision: 'approve' | 'reject' | 'recount') {
    activeDecision.value = decision;
    form.decision = decision;
    form.clearErrors();
    dialogOpen.value = true;
}

function submit() {
    form.post(cash.handovers.resolve.url(props.handover.id), {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset('supervisor_password', 'notes');
        },
    });
}

const statusVariant = (
    status: CashHandover['status'],
): 'default' | 'secondary' | 'destructive' => {
    if (status === 'approved') {
        return 'default';
    }

    if (status === 'rejected') {
        return 'destructive';
    }

    return 'secondary';
};
</script>

<template>
    <Head title="Detalle de entrega de caja" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <PageHeader
            :title="`Entrega de caja — ${handover.cashier_name ?? 'Cajero'}`"
            :description="`Caja: ${handover.register_name ?? '—'} · Sucursal: ${handover.branch_name ?? '—'}`"
        >
            <template #actions>
                <div v-if="handover.status === 'pending'" class="flex gap-2">
                    <Button variant="outline" @click="openDialog('recount')">
                        Solicitar reconteo
                    </Button>
                    <Button variant="destructive" @click="openDialog('reject')">
                        Rechazar
                    </Button>
                    <Button
                        v-if="can('cash.approve-close')"
                        @click="openDialog('approve')"
                    >
                        Aprobar
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Estado</p>
                <Badge :variant="statusVariant(handover.status)">
                    {{ handover.status_label }}
                </Badge>
            </div>
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Fondo inicial</p>
                <p class="text-lg font-semibold">
                    {{ formatCurrency(handover.opening_amount) }}
                </p>
            </div>
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Fecha de solicitud</p>
                <p class="text-lg font-semibold">
                    {{ formatDateTime(handover.requested_at) }}
                </p>
            </div>
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Efectivo esperado</p>
                <p class="text-lg font-semibold">
                    {{ formatCurrency(handover.expected_cash) }}
                </p>
            </div>
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Efectivo contado</p>
                <p class="text-lg font-semibold">
                    {{ formatCurrency(handover.counted_cash) }}
                </p>
            </div>
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Diferencia</p>
                <p
                    class="text-lg font-semibold"
                    :class="
                        Number(handover.difference) < 0
                            ? 'text-destructive'
                            : 'text-green-600'
                    "
                >
                    {{ formatCurrency(handover.difference) }}
                </p>
            </div>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="p-3">Denominación</th>
                        <th class="p-3 text-right">Cantidad</th>
                        <th class="p-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="line in handover.denominations"
                        :key="line.denomination"
                        class="border-b last:border-0"
                    >
                        <td class="p-3">
                            {{ formatCurrency(line.denomination) }}
                        </td>
                        <td class="p-3 text-right">{{ line.quantity }}</td>
                        <td class="p-3 text-right">
                            {{
                                formatCurrency(
                                    line.denomination * line.quantity,
                                )
                            }}
                        </td>
                    </tr>
                    <tr v-if="!handover.denominations.length">
                        <td
                            colspan="3"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Sin denominaciones registradas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border p-4 text-sm">
                <p class="mb-1 font-medium">Notas del cajero</p>
                <p class="text-muted-foreground">
                    {{ handover.cashier_notes ?? 'Sin observaciones.' }}
                </p>
            </div>
            <div
                v-if="handover.status !== 'pending'"
                class="rounded-lg border p-4 text-sm"
            >
                <p class="mb-1 font-medium">
                    Notas del supervisor
                    <span
                        v-if="handover.approver_name"
                        class="font-normal text-muted-foreground"
                        >— {{ handover.approver_name }}</span
                    >
                </p>
                <p class="text-muted-foreground">
                    {{ handover.supervisor_notes ?? 'Sin observaciones.' }}
                </p>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        decisionCopy[activeDecision].title
                    }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <p class="text-sm text-muted-foreground">
                        Confirma tu identidad de supervisor para continuar. Esto
                        no altera tu sesión actual.
                    </p>
                    <FormField
                        label="Correo de supervisor"
                        for="supervisor_email"
                        :error="form.errors.supervisor_email"
                        required
                        tooltip="Correo de un usuario con permiso para aprobar cierres de caja. Confirma su identidad sin cerrar tu sesión actual."
                    >
                        <Input
                            id="supervisor_email"
                            v-model="form.supervisor_email"
                            type="email"
                            autocomplete="off"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Contraseña o PIN de supervisor"
                        for="supervisor_password"
                        :error="form.errors.supervisor_password"
                        required
                        tooltip="Contraseña del supervisor, usada solo para confirmar su identidad en esta decisión."
                    >
                        <Input
                            id="supervisor_password"
                            v-model="form.supervisor_password"
                            type="password"
                            autocomplete="off"
                            required
                        />
                    </FormField>
                    <FormField
                        label="Notas"
                        for="notes"
                        :error="form.errors.notes"
                        :required="activeDecision !== 'approve'"
                        tooltip="Motivo u observaciones de esta decisión. Obligatorio al rechazar o solicitar reconteo, para dejar constancia en la auditoría."
                    >
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            :required="activeDecision !== 'approve'"
                        />
                    </FormField>
                    <p
                        v-if="form.errors.decision"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.decision }}
                    </p>
                    <DialogFooter>
                        <Button
                            type="submit"
                            :variant="
                                activeDecision === 'reject'
                                    ? 'destructive'
                                    : 'default'
                            "
                            :disabled="form.processing"
                        >
                            {{ decisionCopy[activeDecision].confirmLabel }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
