<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import CustomerController from '@/actions/App/Http/Controllers/Sales/CustomerController';
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
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
    address: props.customer?.address ?? '',
    price_list_id: props.customer?.price_list_id ?? null,
    credit_limit: props.customer?.credit_limit ?? '0',
    notes: props.customer?.notes ?? '',
    status: props.customer?.status ?? 'active',
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
            <FormField label="RFC" for="tax_id" :error="form.errors.tax_id">
                <Input id="tax_id" v-model="form.tax_id" />
            </FormField>
            <FormField label="Teléfono" for="phone" :error="form.errors.phone">
                <Input id="phone" v-model="form.phone" />
            </FormField>
            <FormField label="Correo" for="email" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" />
            </FormField>
            <FormField
                label="Lista de precios"
                for="price_list_id"
                :error="form.errors.price_list_id"
            >
                <Select v-model="form.price_list_id">
                    <SelectTrigger id="price_list_id" class="w-full">
                        <SelectValue
                            placeholder="Lista general (por defecto)"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null"
                            >Lista general (por defecto)</SelectItem
                        >
                        <SelectItem
                            v-for="option in priceListOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Límite de crédito"
                for="credit_limit"
                :error="form.errors.credit_limit"
            >
                <Input
                    id="credit_limit"
                    v-model="form.credit_limit"
                    type="number"
                    min="0"
                    step="0.01"
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
