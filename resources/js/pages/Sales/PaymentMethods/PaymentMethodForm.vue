<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CircleHelpIcon } from '@lucide/vue';
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
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
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
                tooltip="Nombre con el que este método de pago se mostrará en el punto de venta."
            >
                <Input id="name" v-model="form.name" required autofocus />
            </FormField>
            <FormField
                label="Código"
                for="code"
                required
                :error="form.errors.code"
                tooltip="Identificador corto y único para este método de pago. Se usa internamente y en reportes."
            >
                <Input id="code" v-model="form.code" required />
            </FormField>
            <FormField
                label="Tipo"
                for="type"
                required
                :error="form.errors.type"
                tooltip="Naturaleza del método de pago. Se usa para reportes y para aplicar reglas específicas (por ejemplo, tarjetas suelen requerir referencia)."
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
                tooltip="Posición de este método al mostrarlo en el punto de venta. Un número menor aparece primero."
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
                tooltip="Un método inactivo deja de estar disponible para seleccionarse en el punto de venta."
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
                <Tooltip>
                    <TooltipTrigger
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        aria-label="Ayuda: Afecta el efectivo físico de la caja"
                    >
                        <CircleHelpIcon class="size-3.5" />
                    </TooltipTrigger>
                    <TooltipContent
                        >Los pagos con este método suman o restan del efectivo
                        contado al cerrar la caja (por ejemplo, «Efectivo» sí
                        afecta; «Transferencia» no).</TooltipContent
                    >
                </Tooltip>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.allows_change" />
                Permite calcular cambio
                <Tooltip>
                    <TooltipTrigger
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        aria-label="Ayuda: Permite calcular cambio"
                    >
                        <CircleHelpIcon class="size-3.5" />
                    </TooltipTrigger>
                    <TooltipContent
                        >El POS puede calcular y mostrar el cambio a devolver
                        cuando el pago recibido con este método excede el
                        total.</TooltipContent
                    >
                </Tooltip>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.requires_reference" />
                Requiere referencia / datos de tarjeta
                <Tooltip>
                    <TooltipTrigger
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        aria-label="Ayuda: Requiere referencia / datos de tarjeta"
                    >
                        <CircleHelpIcon class="size-3.5" />
                    </TooltipTrigger>
                    <TooltipContent
                        >El cajero deberá capturar un número de referencia,
                        autorización o últimos dígitos de tarjeta al registrar
                        el pago.</TooltipContent
                    >
                </Tooltip>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <Checkbox v-model="form.opens_cash_drawer" />
                Abre el cajón de dinero
                <Tooltip>
                    <TooltipTrigger
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        aria-label="Ayuda: Abre el cajón de dinero"
                    >
                        <CircleHelpIcon class="size-3.5" />
                    </TooltipTrigger>
                    <TooltipContent
                        >Al cobrar con este método se envía la señal para
                        abrir físicamente el cajón de dinero conectado a la
                        caja.</TooltipContent
                    >
                </Tooltip>
            </label>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ paymentMethod ? 'Guardar cambios' : 'Crear método de pago' }}
        </Button>
    </form>
</template>
