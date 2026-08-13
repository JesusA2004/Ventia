<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import WarehouseController from '@/actions/App/Http/Controllers/Settings/WarehouseController';
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
import type { Branch, Warehouse, WarehouseType } from '@/types';

const props = defineProps<{
    warehouse?: Warehouse;
    branches: Branch[];
}>();

const warehouseTypes: { value: WarehouseType; label: string }[] = [
    { value: 'general', label: 'General' },
    { value: 'sales_floor', label: 'Piso de venta' },
    { value: 'storage', label: 'Almacenamiento' },
    { value: 'returns', label: 'Devoluciones' },
    { value: 'damaged', label: 'Dañados' },
    { value: 'transit', label: 'Tránsito' },
];

const form = useForm({
    branch_id: props.warehouse?.branch_id ?? props.branches[0]?.id ?? null,
    name: props.warehouse?.name ?? '',
    code: props.warehouse?.code ?? '',
    type: props.warehouse?.type ?? 'general',
    allows_sale: props.warehouse?.allows_sale ?? true,
    status: props.warehouse?.status ?? 'active',
});

function submit() {
    if (props.warehouse) {
        form.put(WarehouseController.update.url(props.warehouse.id));
    } else {
        form.post(WarehouseController.store.url());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Sucursal"
                for="branch_id"
                required
                :error="form.errors.branch_id"
            >
                <Select v-model="form.branch_id">
                    <SelectTrigger id="branch_id" class="w-full">
                        <SelectValue placeholder="Selecciona una sucursal" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="branch in branches"
                            :key="branch.id"
                            :value="branch.id"
                        >
                            {{ branch.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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
                            v-for="option in warehouseTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
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
                label="Código"
                for="code"
                :error="form.errors.code"
                tooltip="Identificador corto y único dentro de la sucursal. Déjalo vacío para que se genere automáticamente (por ejemplo, ALM-002)."
            >
                <Input
                    id="code"
                    v-model="form.code"
                    class="uppercase"
                    placeholder="Automático si se deja vacío"
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
                label="Permite venta"
                for="allows_sale"
                :error="form.errors.allows_sale"
                tooltip="Indica si este almacén puede utilizarse como origen de inventario para ventas."
            >
                <div class="flex h-9 items-center">
                    <Switch id="allows_sale" v-model="form.allows_sale" />
                </div>
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ warehouse ? 'Guardar cambios' : 'Crear almacén' }}
        </Button>
    </form>
</template>
