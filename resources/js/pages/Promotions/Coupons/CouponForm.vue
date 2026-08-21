<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import CouponController from '@/actions/App/Http/Controllers/Promotions/CouponController';
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
import type { Coupon, PromotionScopeOption } from '@/types';

const props = defineProps<{
    coupon?: Coupon;
    branchOptions: PromotionScopeOption[];
    categoryOptions: PromotionScopeOption[];
}>();

const form = useForm({
    code: props.coupon?.code ?? '',
    name: props.coupon?.name ?? '',
    description: props.coupon?.description ?? '',
    type: props.coupon?.type ?? 'percentage',
    value: props.coupon?.value ?? '',
    starts_at: toDatetimeLocal(props.coupon?.starts_at),
    ends_at: toDatetimeLocal(props.coupon?.ends_at),
    status: props.coupon?.status ?? 'active',
    min_purchase_amount: props.coupon?.min_purchase_amount ?? '',
    usage_limit: props.coupon?.usage_limit ?? '',
    usage_limit_per_customer: props.coupon?.usage_limit_per_customer ?? '',
    combinable: props.coupon?.combinable ?? false,
    notes: props.coupon?.notes ?? '',
    branch_ids: props.coupon?.branch_ids ?? [],
    category_ids: props.coupon?.category_ids ?? [],
    product_ids: props.coupon?.product_ids ?? [],
});

function toDatetimeLocal(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value.slice(0, 16);
}

function submit() {
    if (props.coupon) {
        form.put(CouponController.update.url(props.coupon.id));
    } else {
        form.post(CouponController.store.url());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Código"
                for="code"
                required
                :error="form.errors.code"
                tooltip="El código que el cajero captura en el POS (por ejemplo, VERANO10). Se guarda en mayúsculas automáticamente."
            >
                <Input
                    id="code"
                    v-model="form.code"
                    required
                    autofocus
                    class="uppercase"
                    placeholder="VERANO10"
                />
            </FormField>
            <FormField
                label="Nombre"
                for="name"
                required
                :error="form.errors.name"
                tooltip="Nombre descriptivo del cupón, visible para el cajero."
            >
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="Promoción de verano"
                />
            </FormField>
            <FormField
                label="Descripción"
                for="description"
                :error="form.errors.description"
                tooltip="Detalle opcional, útil para recordar el propósito del cupón."
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
                tooltip="Fecha y hora desde la que el cupón puede usarse. Déjalo vacío para que aplique desde ahora."
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
                tooltip="Fecha y hora hasta la que el cupón puede usarse. Déjalo vacío para que no tenga fecha de fin."
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
                tooltip="Un cupón inactivo nunca puede usarse, aunque esté dentro de su vigencia."
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
                label="Compra mínima"
                for="min_purchase_amount"
                :error="form.errors.min_purchase_amount"
                tooltip="Monto mínimo que debe alcanzar el total de la venta para poder usar este cupón. Déjalo vacío si no aplica."
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
                label="Límite de usos"
                for="usage_limit"
                :error="form.errors.usage_limit"
                tooltip="Número máximo de veces que este cupón puede usarse en total. Por ejemplo, 1 para que sea de un solo uso. Déjalo vacío para no limitarlo."
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
                tooltip="Número máximo de veces que un mismo cliente registrado puede usar este cupón. No aplica a ventas de público general."
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
                tooltip="Indica si este cupón puede usarse junto con una promoción automática que también aplique a la venta."
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
            :initial-products="coupon?.products"
        />

        <Button type="submit" :disabled="form.processing">
            {{ coupon ? 'Guardar cambios' : 'Crear cupón' }}
        </Button>
    </form>
</template>
