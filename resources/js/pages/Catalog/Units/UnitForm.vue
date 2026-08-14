<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import UnitController from '@/actions/App/Http/Controllers/Catalog/UnitController';
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
import type { Unit, UnitType } from '@/types';

const props = defineProps<{
    unit?: Unit;
    baseUnitOptions: Unit[];
}>();

const unitTypes: { value: UnitType; label: string }[] = [
    { value: 'piece', label: 'Pieza' },
    { value: 'weight', label: 'Peso' },
    { value: 'volume', label: 'Volumen' },
    { value: 'length', label: 'Longitud' },
    { value: 'package', label: 'Paquete' },
    { value: 'service', label: 'Servicio' },
    { value: 'other', label: 'Otro' },
];

const form = useForm({
    name: props.unit?.name ?? '',
    symbol: props.unit?.symbol ?? '',
    type: props.unit?.type ?? 'piece',
    decimal_places: props.unit?.decimal_places ?? 0,
    allows_fraction: props.unit?.allows_fraction ?? false,
    conversion_factor: props.unit?.conversion_factor ?? '',
    base_unit_id: props.unit?.base_unit_id ?? null,
    status: props.unit?.status ?? 'active',
});

function submit() {
    if (props.unit) {
        form.put(UnitController.update.url(props.unit.id));
    } else {
        form.post(UnitController.store.url());
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
                    placeholder="Kilogramo"
                />
            </FormField>
            <FormField
                label="Símbolo"
                for="symbol"
                required
                :error="form.errors.symbol"
            >
                <Input
                    id="symbol"
                    v-model="form.symbol"
                    required
                    placeholder="kg"
                />
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
                        <SelectItem
                            v-for="option in unitTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Decimales permitidos"
                for="decimal_places"
                :error="form.errors.decimal_places"
                tooltip="Cuántos dígitos después del punto decimal se permiten al capturar cantidades de esta unidad (por ejemplo, 2 permite 1.25 kg)."
            >
                <Input
                    id="decimal_places"
                    v-model.number="form.decimal_places"
                    type="number"
                    min="0"
                    max="4"
                />
            </FormField>
            <FormField
                label="Unidad base (conversión)"
                for="base_unit_id"
                :error="form.errors.base_unit_id"
                tooltip="Unidad a la que se puede convertir esta, usando el factor de conversión. Déjalo vacío si esta unidad no se convierte a otra."
            >
                <Select v-model="form.base_unit_id">
                    <SelectTrigger id="base_unit_id" class="w-full">
                        <SelectValue placeholder="Sin conversión" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Sin conversión</SelectItem>
                        <SelectItem
                            v-for="option in baseUnitOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }} ({{ option.symbol }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Factor de conversión"
                for="conversion_factor"
                :error="form.errors.conversion_factor"
                tooltip="Cuántas unidades base equivalen a 1 de esta unidad (por ejemplo, si la unidad base es 'Gramo', un factor de 1000 significa que 1 Kilogramo = 1000 Gramos)."
            >
                <Input
                    id="conversion_factor"
                    v-model="form.conversion_factor"
                    type="number"
                    step="0.000001"
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
                label="Permite fracciones (a granel)"
                for="allows_fraction"
                :error="form.errors.allows_fraction"
                tooltip="Actívalo para productos que se venden a granel (kg, litros, metros...), donde la cantidad puede tener decimales. Desactívalo para unidades que solo se venden en piezas completas."
            >
                <div class="flex h-9 items-center">
                    <Switch
                        id="allows_fraction"
                        v-model="form.allows_fraction"
                    />
                </div>
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ unit ? 'Guardar cambios' : 'Crear unidad' }}
        </Button>
    </form>
</template>
