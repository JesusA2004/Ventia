<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CustomerController from '@/actions/App/Http/Controllers/Sales/CustomerController';
import FormCurrencyInput from '@/components/forms/FormCurrencyInput.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { PHONE_COUNTRY_CODES } from '@/lib/phone-codes';
import type { Customer, PriceList } from '@/types';

const props = defineProps<{
    customer?: Customer;
    priceListOptions: PriceList[];
}>();

const form = useForm({
    customer_type: props.customer?.customer_type ?? 'individual',
    name: props.customer?.name ?? '',
    legal_name: props.customer?.legal_name ?? '',
    tax_id: props.customer?.tax_id ?? '',
    phone_country_code: props.customer?.phone_country_code ?? '+52',
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
    address: props.customer?.address ?? '',
    price_list_id: props.customer?.price_list_id ?? null,
    credit_limit: props.customer?.credit_limit ?? '0',
    notes: props.customer?.notes ?? '',
    status: props.customer?.status ?? 'active',
});

const defaultPriceList = computed(
    () => props.priceListOptions.find((list) => list.is_default) ?? null,
);

const rfcMaxLength = computed(() =>
    form.customer_type === 'business' ? 12 : 13,
);

const rfcHelp = computed(() => {
    if (form.customer_type === 'business') {
        return 'RFC de persona moral: 12 caracteres.';
    }

    if (form.customer_type === 'individual') {
        return 'RFC de persona física: 13 caracteres.';
    }

    return null;
});

function submit() {
    if (props.customer) {
        form.put(CustomerController.update.url(props.customer.id));
    } else {
        form.post(CustomerController.store.url());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Tipo de cliente"
                for="customer_type"
                required
                :error="form.errors.customer_type"
            >
                <Select v-model="form.customer_type">
                    <SelectTrigger id="customer_type" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="individual"
                            >Persona física</SelectItem
                        >
                        <SelectItem value="business">Empresa</SelectItem>
                        <SelectItem value="general_public"
                            >Público general</SelectItem
                        >
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Nombre"
                for="name"
                required
                :error="form.errors.name"
            >
                <Input id="name" v-model="form.name" required autofocus />
            </FormField>
            <FormField
                label="Razón social"
                for="legal_name"
                :error="form.errors.legal_name"
            >
                <Input id="legal_name" v-model="form.legal_name" />
            </FormField>
            <FormField
                label="RFC"
                for="tax_id"
                :error="form.errors.tax_id"
                :tooltip="rfcHelp ?? undefined"
            >
                <Input
                    id="tax_id"
                    v-model="form.tax_id"
                    :maxlength="rfcMaxLength"
                    class="uppercase"
                    placeholder="XAXX010101000"
                />
            </FormField>
            <FormField
                label="Teléfono"
                for="phone"
                :error="form.errors.phone ?? form.errors.phone_country_code"
            >
                <div class="flex gap-2">
                    <Select v-model="form.phone_country_code">
                        <SelectTrigger id="phone_country_code" class="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="code in PHONE_COUNTRY_CODES"
                                :key="code.value"
                                :value="code.value"
                            >
                                {{ code.value }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        inputmode="numeric"
                        placeholder="7771234567"
                        class="flex-1"
                    />
                </div>
            </FormField>
            <FormField label="Correo" for="email" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" />
            </FormField>
            <FormField
                label="Lista de precios"
                for="price_list_id"
                :error="form.errors.price_list_id"
                tooltip="Si no eliges una lista, se usa el precio base del producto (o la lista predeterminada de la empresa, si existe)."
            >
                <Select v-model="form.price_list_id">
                    <SelectTrigger id="price_list_id" class="w-full">
                        <SelectValue placeholder="Precio base del producto" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">
                            Precio base del producto (sin lista específica)
                        </SelectItem>
                        <SelectItem
                            v-for="option in priceListOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name
                            }}{{ option.is_default ? ' (predeterminada)' : '' }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p
                    v-if="defaultPriceList && !form.price_list_id"
                    class="text-xs text-muted-foreground"
                >
                    Empresa: la lista predeterminada es «{{
                        defaultPriceList.name
                    }}».
                </p>
            </FormField>
            <FormCurrencyInput
                id="credit_limit"
                v-model="form.credit_limit"
                label="Límite de crédito"
                tooltip="Monto máximo de crédito autorizado para este cliente en ventas a crédito."
                :error="form.errors.credit_limit"
            />
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
            <FormField
                label="Dirección"
                for="address"
                class="sm:col-span-2"
                :error="form.errors.address"
            >
                <Textarea id="address" v-model="form.address" rows="2" />
            </FormField>
            <FormField
                label="Notas"
                for="notes"
                class="sm:col-span-2"
                :error="form.errors.notes"
            >
                <Textarea id="notes" v-model="form.notes" rows="2" />
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ customer ? 'Guardar cambios' : 'Crear cliente' }}
        </Button>
    </form>
</template>
