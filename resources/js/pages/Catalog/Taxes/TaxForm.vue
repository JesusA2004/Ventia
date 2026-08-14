<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TaxController from '@/actions/App/Http/Controllers/Catalog/TaxController';
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
import type { Tax, TaxType } from '@/types';

const props = defineProps<{
    tax?: Tax;
}>();

const taxTypes: { value: TaxType; label: string }[] = [
    { value: 'percentage', label: 'Porcentaje' },
    { value: 'fixed', label: 'Monto fijo' },
    { value: 'exempt', label: 'Exento' },
];

const form = useForm({
    name: props.tax?.name ?? '',
    code: props.tax?.code ?? '',
    rate: props.tax?.rate ?? '0',
    type: props.tax?.type ?? 'percentage',
    included_in_price: props.tax?.included_in_price ?? true,
    status: props.tax?.status ?? 'active',
});

function submit() {
    if (props.tax) {
        form.put(TaxController.update.url(props.tax.id));
    } else {
        form.post(TaxController.store.url());
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
                tooltip="Nombre con el que este impuesto se mostrará en el punto de venta y en los tickets."
            >
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    autofocus
                    placeholder="IVA 16%"
                />
            </FormField>
            <FormField
                label="Código"
                for="code"
                required
                :error="form.errors.code"
                tooltip="Identificador corto y único para este impuesto. Se usa internamente y en reportes."
            >
                <Input
                    id="code"
                    v-model="form.code"
                    required
                    class="uppercase"
                    placeholder="IVA16"
                />
            </FormField>
            <FormField
                label="Tipo"
                for="type"
                required
                :error="form.errors.type"
                tooltip="Cómo se calcula este impuesto: como porcentaje del precio, como monto fijo, o exento (0%)."
            >
                <Select v-model="form.type">
                    <SelectTrigger id="type" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in taxTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Tasa (%)"
                for="rate"
                required
                :error="form.errors.rate"
                tooltip="Porcentaje o monto que se aplica sobre el precio del producto para calcular este impuesto."
            >
                <Input
                    id="rate"
                    v-model="form.rate"
                    type="number"
                    step="0.0001"
                    min="0"
                    max="100"
                />
            </FormField>
            <FormField
                label="Estado"
                for="status"
                required
                :error="form.errors.status"
                tooltip="Un impuesto inactivo deja de estar disponible para asignarse a productos nuevos."
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
                label="Incluido en el precio"
                for="included_in_price"
                :error="form.errors.included_in_price"
                tooltip="Si está activo, el precio del producto ya incluye este impuesto. Si está inactivo, el impuesto se suma al cobrar."
            >
                <div class="flex h-9 items-center">
                    <Switch
                        id="included_in_price"
                        v-model="form.included_in_price"
                    />
                </div>
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ tax ? 'Guardar cambios' : 'Crear impuesto' }}
        </Button>
    </form>
</template>
