<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PromotionController from '@/actions/App/Http/Controllers/Promotions/PromotionController';
import FormField from '@/components/forms/FormField.vue';
import PromotionScopeFields from '@/components/promotions/PromotionScopeFields.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { Promotion, PromotionScopeOption } from '@/types';

const props = defineProps<{
    promotion?: Promotion;
    branchOptions: PromotionScopeOption[];
    categoryOptions: PromotionScopeOption[];
}>();

const form = useForm({
    name: props.promotion?.name ?? '',
    description: props.promotion?.description ?? '',
    type: props.promotion?.type ?? 'percentage',
    value: props.promotion?.value ?? '',
    starts_at: toDatetimeLocal(props.promotion?.starts_at),
    ends_at: toDatetimeLocal(props.promotion?.ends_at),
    status: props.promotion?.status ?? 'active',
    min_purchase_amount: props.promotion?.min_purchase_amount ?? '',
    min_quantity: props.promotion?.min_quantity ?? '',
    usage_limit: props.promotion?.usage_limit ?? '',
    usage_limit_per_customer: props.promotion?.usage_limit_per_customer ?? '',
    priority: props.promotion?.priority ?? 0,
    combinable: props.promotion?.combinable ?? false,
    notes: props.promotion?.notes ?? '',
    branch_ids: props.promotion?.branch_ids ?? [],
    category_ids: props.promotion?.category_ids ?? [],
    product_ids: props.promotion?.product_ids ?? [],
});

/** Inertia sends datetime-local values as-is; the backend parses them with Carbon. */
function toDatetimeLocal(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 16);
}

function submit() {
    if (props.promotion) {
        form.put(PromotionController.update.url(props.promotion.id));
    } else {
        form.post(PromotionController.store.url());
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
                tooltip="Nombre interno de la promoción (por ejemplo, '10% Bebidas'). No se muestra al cliente en el ticket, pero sí al cajero en el POS."
                class="sm:col-span-2"
            >
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    autofocus
                    placeholder="10% en Bebidas"
                />
            </FormField>
            <FormField
                label="Descripción"
                for="description"
                :error="form.errors.description"
                tooltip="Detalle opcional, útil para recordar por qué existe esta promoción."
                class="sm:col-span-2"
            >
                <Textarea id="description" v-model="form.description" />
            </FormField>
            <FormField
                label="Tipo de descuento"
                for="type"
                required
                :error="form.errors.type"
                tooltip="Porcentaje: un % del importe elegible. Monto fijo: una cantidad exacta en pesos."
            >
                <Select v-model="form.type">
                    <SelectTrigger id="type" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="percentage">Porcentaje</SelectItem>
                        <SelectItem value="fixed_amount">Monto fijo</SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Valor"
                for="value"
                required
                :error="form.errors.value"
                :tooltip="
                    form.type === 'percentage'
                        ? 'Porcentaje de descuento, de 0 a 100.'
                        : 'Monto fijo de descuento, en pesos.'
                "
            >
                <Input
                    id="value"
                    v-model="form.value"
                    type="number"
                    min="0.01"
                    :max="form.type === 'percentage' ? 100 : undefined"
                    step="0.01"
                />
            </FormField>
            <FormField
                label="Inicia"
                for="starts_at"
                :error="form.errors.starts_at"
                tooltip="Fecha y hora desde la que la promoción puede aplicarse. Déjalo vacío para que aplique desde ahora."
            >
                <Input
                    id="starts_at"
                    v-model="form.starts_at"
                    type="datetime-local"
                />
            </FormField>
            <FormField
                label="Termina"
                for="ends_at"
                :error="form.errors.ends_at"
                tooltip="Fecha y hora hasta la que la promoción puede aplicarse. Déjalo vacío para que no tenga fecha de fin."
            >
                <Input
                    id="ends_at"
                    v-model="form.ends_at"
                    type="datetime-local"
                />
            </FormField>
            <FormField
                label="Estado"
                for="status"
                required
                :error="form.errors.status"
                tooltip="Una promoción inactiva nunca se aplica, aunque esté dentro de su vigencia."
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
            <FormField
                label="Prioridad"
                for="priority"
                :error="form.errors.priority"
                tooltip="Cuando más de una promoción podría aplicar, se usa la de mayor prioridad (número más alto)."
            >
                <Input
                    id="priority"
                    v-model.number="form.priority"
                    type="number"
                    min="0"
                />
            </FormField>
            <FormField
                label="Compra mínima"
                for="min_purchase_amount"
                :error="form.errors.min_purchase_amount"
                tooltip="Monto mínimo que debe alcanzar el total de la venta para que esta promoción pueda aplicarse. Déjalo vacío si no aplica."
            >
                <Input
                    id="min_purchase_amount"
                    v-model="form.min_purchase_amount"
                    type="number"
                    min="0"
                    step="0.01"
                />
            </FormField>
            <FormField
                label="Cantidad mínima"
                for="min_quantity"
                :error="form.errors.min_quantity"
                tooltip="Solo aplica si la promoción está limitada a productos o categorías específicas: cantidad mínima de esos productos que debe llevar la venta."
            >
                <Input
                    id="min_quantity"
                    v-model="form.min_quantity"
                    type="number"
                    min="0"
                    step="0.0001"
                />
            </FormField>
            <FormField
                label="Límite de usos"
                for="usage_limit"
                :error="form.errors.usage_limit"
                tooltip="Número máximo de veces que esta promoción puede aplicarse en total, sumando todas las ventas. Déjalo vacío para no limitarlo."
            >
                <Input
                    id="usage_limit"
                    v-model="form.usage_limit"
                    type="number"
                    min="1"
                />
            </FormField>
            <FormField
                label="Límite por cliente"
                for="usage_limit_per_customer"
                :error="form.errors.usage_limit_per_customer"
                tooltip="Número máximo de veces que un mismo cliente registrado puede aprovechar esta promoción. No aplica a ventas de público general."
            >
                <Input
                    id="usage_limit_per_customer"
                    v-model="form.usage_limit_per_customer"
                    type="number"
                    min="1"
                />
            </FormField>
            <FormField
                label="Combinable"
                for="combinable"
                tooltip="Indica si esta promoción puede aplicarse junto con un cupón. Dos promociones automáticas nunca se combinan entre sí: solo aplica la de mayor prioridad."
                :error="form.errors.combinable"
            >
                <div class="flex h-9 items-center">
                    <Switch id="combinable" v-model="form.combinable" />
                </div>
            </FormField>
            <FormField
                label="Observaciones"
                for="notes"
                :error="form.errors.notes"
                tooltip="Notas internas, no visibles para el cliente."
                class="sm:col-span-2"
            >
                <Textarea id="notes" v-model="form.notes" />
            </FormField>
        </div>

        <PromotionScopeFields
            v-model:branch-ids="form.branch_ids"
            v-model:category-ids="form.category_ids"
            v-model:product-ids="form.product_ids"
            :branch-options="branchOptions"
            :category-options="categoryOptions"
            :initial-products="promotion?.products"
        />

        <Button type="submit" :disabled="form.processing">
            {{ promotion ? 'Guardar cambios' : 'Crear promoción' }}
        </Button>
    </form>
</template>
