<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import cash from '@/routes/cash';
import type { CashMovement, CashSession, CashSessionSummary } from '@/types';

const props = defineProps<{
    session: CashSession;
    movements: CashMovement[];
    summary: CashSessionSummary | null;
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: cash.sessions.index() },
            { title: 'Detalle de sesión', href: '#' },
        ],
    },
});

const movementDialogOpen = ref(false);
const movementForm = useForm({
    type: 'deposit' as 'deposit' | 'withdrawal' | 'expense',
    amount: '',
    reason: '',
    notes: '',
});

function submitMovement() {
    movementForm.post(cash.sessions.movements.store.url(props.session.id), {
        preserveScroll: true,
        onSuccess: () => {
            movementDialogOpen.value = false;
            movementForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Detalle de sesión de caja" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Caja: ${session.register_name}`"
            :description="`Usuario: ${session.user_name}`"
        >
            <template #actions>
                <div class="flex gap-2">
                    <Dialog
                        v-if="session.status === 'open'"
                        v-model:open="movementDialogOpen"
                    >
                        <DialogTrigger as-child>
                            <Button variant="outline"
                                >Registrar movimiento</Button
                            >
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle
                                    >Registrar movimiento de caja</DialogTitle
                                >
                            </DialogHeader>
                            <form
                                class="space-y-4"
                                @submit.prevent="submitMovement"
                            >
                                <div class="grid gap-2">
                                    <select
                                        v-model="movementForm.type"
                                        class="h-9 rounded-md border bg-background px-2 text-sm"
                                    >
                                        <option
                                            v-if="can('cash.register-deposit')"
                                            value="deposit"
                                        >
                                            Depósito
                                        </option>
                                        <option
                                            v-if="
                                                can('cash.register-withdrawal')
                                            "
                                            value="withdrawal"
                                        >
                                            Retiro
                                        </option>
                                        <option
                                            v-if="can('cash.register-expense')"
                                            value="expense"
                                        >
                                            Gasto
                                        </option>
                                    </select>
                                    <Input
                                        v-model="movementForm.amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="Monto"
                                        required
                                    />
                                    <Input
                                        v-model="movementForm.reason"
                                        placeholder="Motivo"
                                        required
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        :disabled="movementForm.processing"
                                        >Registrar</Button
                                    >
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Button v-if="session.status === 'open'" as-child>
                        <Link :href="cash.sessions.closeScreen.url(session.id)"
                            >Cerrar caja</Link
                        >
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Estado</p>
                <Badge
                    :variant="
                        session.status === 'open'
                            ? 'default'
                            : session.status === 'force_closed'
                              ? 'destructive'
                              : 'secondary'
                    "
                >
                    {{ session.status_label }}
                </Badge>
            </div>
            <div class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">Fondo inicial</p>
                <p class="text-lg font-semibold">
                    ${{ Number(session.opening_amount).toFixed(2) }}
                </p>
            </div>
            <div
                v-if="session.expected_cash !== null"
                class="rounded-lg border p-3 text-sm"
            >
                <p class="text-muted-foreground">Efectivo esperado</p>
                <p class="text-lg font-semibold">
                    ${{ Number(session.expected_cash).toFixed(2) }}
                </p>
            </div>
            <div
                v-if="session.difference !== null"
                class="rounded-lg border p-3 text-sm"
            >
                <p class="text-muted-foreground">Diferencia</p>
                <p
                    class="text-lg font-semibold"
                    :class="
                        Number(session.difference) < 0
                            ? 'text-destructive'
                            : 'text-green-600'
                    "
                >
                    ${{ Number(session.difference).toFixed(2) }}
                </p>
            </div>
            <div v-else-if="summary" class="rounded-lg border p-3 text-sm">
                <p class="text-muted-foreground">
                    Efectivo esperado (en curso)
                </p>
                <p class="text-lg font-semibold">
                    ${{ Number(summary.expected_cash).toFixed(2) }}
                </p>
            </div>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Tipo</th>
                        <th class="p-3">Usuario</th>
                        <th class="p-3">Motivo</th>
                        <th class="p-3 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="movement in movements"
                        :key="movement.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-3">
                            {{
                                new Date(movement.occurred_at).toLocaleString()
                            }}
                        </td>
                        <td class="p-3">{{ movement.type_label }}</td>
                        <td class="p-3">{{ movement.user_name }}</td>
                        <td class="p-3">{{ movement.reason }}</td>
                        <td
                            class="p-3 text-right"
                            :class="
                                movement.is_inflow
                                    ? 'text-green-600'
                                    : 'text-destructive'
                            "
                        >
                            {{ movement.is_inflow ? '+' : '-' }}${{
                                Number(movement.amount).toFixed(2)
                            }}
                        </td>
                    </tr>
                    <tr v-if="!movements.length">
                        <td
                            colspan="5"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Sin movimientos registrados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
