<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PaymentMethodController from '@/actions/App/Http/Controllers/Sales/PaymentMethodController';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PaymentMethod } from '@/types';

const props = defineProps<{
    paymentMethod?: PaymentMethod;
}>();

const form = useForm({
    name: props.paymentMethod?.name ?? '',
    code: props.paymentMethod?.code ?? '',
    type: props.paymentMethod?.type ?? 'cash',
    requires_reference: props.paymentMethod?.requires_reference ?? false,
    opens_cash_drawer: props.paymentMethod?.opens_cash_drawer ?? false,
    affects_cash: props.paymentMethod?.affects_cash ?? false,
    allows_change: props.paymentMethod?.allows_change ?? false,
    sort_order: props.paymentMethod?.sort_order ?? 0,
    status: props.paymentMethod?.status ?? 'active',
});

function submit() {
    if (props.paymentMethod) {
        form.put(PaymentMethodController.update.url(props.paymentMethod.id));
    } else {
        form.post(PaymentMethodController.store.url());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Nombre"
                for="name"
                required
                :error="form.errors.name"
            >
                <Input id="name" v-model="form.name" required autofocus />
            </FormField>
            <FormField
                label="Código"
                for="code"
                required
                :error="form.errors.code"
            >
                <Input id="code" v-model="form.code" required />
            </FormField>
            <FormField
                label="Tipo"
                for="type"
                required
                :error="form.errors.type"
            >
                <Select v-model="form.type">
                    <SelectTrigger id="type" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="cash">Efectivo</SelectItem>
                        <SelectItem value="card_debit"
                            >Tarjeta de débito</SelectItem
                        >
                        <SelectItem value="card_credit"
                            >Tarjeta de crédito</SelectItem
                        >
                        <SelectItem value="transfer">Transferencia</SelectItem>
                        <SelectItem value="voucher">Vale</SelectItem>
                        <SelectItem value="customer_credit"
                            >Crédito del cliente</SelectItem
                        >
                        <SelectItem value="other">Otro</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Orden"
                for="sort_order"
                :error="form.errors.sort_order"
            >
                <Input
                    id="sort_order"
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                />
            </FormField>
            <FormField
                label="Estado"
                for="status"
                required
                :error="form.errors.status"
            >
                <Select v-model="form.status">
                    <SelectTrigger id="status" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="active">Activo</SelectItem>
                        <SelectItem value="inactive">Inactivo</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
        </div>

        <div class="space-y-3">
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.affects_cash" />
                Afecta el efectivo físico de la caja
            </label>
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.allows_change" />
                Permite calcular cambio
            </label>
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.requires_reference" />
                Requiere referencia / datos de tarjeta
            </label>
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.opens_cash_drawer" />
                Abre el cajón de dinero
            </label>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ paymentMethod ? 'Guardar cambios' : 'Crear método de pago' }}
        </Button>
    </form>
</template>
