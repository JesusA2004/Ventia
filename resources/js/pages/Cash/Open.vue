<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LockKeyholeIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import AppAlert from '@/components/feedback/AppAlert.vue';
import FormCurrencyInput from '@/components/forms/FormCurrencyInput.vue';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import cash from '@/routes/cash';
import { create as createRegister } from '@/routes/settings/registers';

defineProps<{
    registerOptions: {
        id: number;
        name: string;
        branch_id: number;
        branch_name: string;
    }[];
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Abrir caja', href: cash.sessions.create() }],
    },
});

const form = useForm({
    register_id: null as number | null,
    opening_amount: '0',
    opening_notes: '',
});

function submit() {
    form.post(cash.sessions.store.url());
}
</script>

<template>
    <Head title="Abrir caja" />

    <div class="mx-auto flex max-w-lg flex-col items-center gap-6 py-10">
        <LockKeyholeIcon class="size-10 text-muted-foreground" />
        <PageHeader
            title="Abrir caja"
            description="Selecciona tu caja y captura el fondo inicial para comenzar a vender."
            class="text-center"
        />

        <AppAlert v-if="registerOptions.length" variant="info" class="w-full">
            Para acceder al punto de venta primero selecciona una caja y
            registra el fondo inicial.
        </AppAlert>

        <div
            v-if="!registerOptions.length"
            class="w-full rounded-xl border p-6"
        >
            <EmptyState
                :icon="LockKeyholeIcon"
                title="No hay cajas configuradas"
                description="Un administrador debe crear una caja antes de poder abrir un turno."
            >
                <template v-if="can('registers.manage')" #action>
                    <Button as-child>
                        <Link :href="createRegister().url">Crear caja</Link>
                    </Button>
                </template>
            </EmptyState>
        </div>

        <form
            v-else
            class="w-full space-y-4 rounded-xl border p-6"
            @submit.prevent="submit"
        >
            <FormField
                label="Caja"
                for="register_id"
                required
                :error="form.errors.register_id"
            >
                <Select v-model="form.register_id">
                    <SelectTrigger id="register_id" class="w-full">
                        <SelectValue placeholder="Selecciona una caja" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in registerOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }} — {{ option.branch_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>

            <FormCurrencyInput
                id="opening_amount"
                v-model="form.opening_amount"
                label="Fondo inicial"
                required
                tooltip="Efectivo con el que se abre la caja, antes de registrar cualquier venta."
                :error="form.errors.opening_amount"
            />

            <FormField
                label="Notas de apertura"
                for="opening_notes"
                :error="form.errors.opening_notes"
            >
                <Textarea
                    id="opening_notes"
                    v-model="form.opening_notes"
                    rows="2"
                />
            </FormField>

            <Button
                type="submit"
                class="w-full"
                size="lg"
                :disabled="form.processing"
            >
                {{
                    form.processing
                        ? 'Abriendo caja...'
                        : 'Abrir caja y continuar al punto de venta'
                }}
            </Button>
        </form>
    </div>
</template>
