<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PriceListController from '@/actions/App/Http/Controllers/Catalog/PriceListController';
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
import { Switch } from '@/components/ui/switch';
import type { PriceList } from '@/types';

const props = defineProps<{
    priceList?: PriceList;
}>();

const form = useForm({
    name: props.priceList?.name ?? '',
    code: props.priceList?.code ?? '',
    currency: props.priceList?.currency ?? 'MXN',
    priority: props.priceList?.priority ?? 0,
    is_default: props.priceList?.is_default ?? false,
    status: props.priceList?.status ?? 'active',
});

function submit() {
    if (props.priceList) {
        form.put(PriceListController.update.url(props.priceList.id));
    } else {
        form.post(PriceListController.store.url());
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
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    autofocus
                    placeholder="Mayoreo"
                />
            </FormField>
            <FormField
                label="Código"
                for="code"
                required
                :error="form.errors.code"
            >
                <Input
                    id="code"
                    v-model="form.code"
                    required
                    class="uppercase"
                />
            </FormField>
            <FormField
                label="Moneda (ISO 4217)"
                for="currency"
                required
                :error="form.errors.currency"
            >
                <Input
                    id="currency"
                    v-model="form.currency"
                    maxlength="3"
                    class="uppercase"
                />
            </FormField>
            <FormField
                label="Prioridad"
                for="priority"
                :error="form.errors.priority"
            >
                <Input
                    id="priority"
                    v-model.number="form.priority"
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
            <FormField
                label="Lista predeterminada de la empresa"
                for="is_default"
                tooltip="Se usa automáticamente para nuevos clientes que no elijan una lista de precios explícita. Solo puede haber una lista predeterminada por empresa."
                :error="form.errors.is_default"
            >
                <div class="flex h-9 items-center">
                    <Switch id="is_default" v-model="form.is_default" />
                </div>
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ priceList ? 'Guardar cambios' : 'Crear lista de precios' }}
        </Button>
    </form>
</template>
