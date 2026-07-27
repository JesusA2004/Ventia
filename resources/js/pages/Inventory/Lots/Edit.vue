<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ProductLotController from '@/actions/App/Http/Controllers/Inventory/ProductLotController';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/inventory/lots';
import type { ProductLot } from '@/types';

const props = defineProps<{
    lot: ProductLot;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Lotes y caducidades', href: index() },
            { title: 'Editar lote', href: '#' },
        ],
    },
});

const form = useForm({
    lot_number: props.lot.lot_number,
    manufacture_date: props.lot.manufacture_date ?? '',
    expiration_date: props.lot.expiration_date ?? '',
    received_at: props.lot.received_at ?? '',
    cost: props.lot.cost,
    status: props.lot.status,
});

function submit() {
    form.put(ProductLotController.update.url(props.lot.id));
}
</script>

<template>
    <Head title="Editar lote" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar lote «${props.lot.lot_number}»`"
            :description="props.lot.product_name"
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Número de lote"
                    for="lot_number"
                    required
                    :error="form.errors.lot_number"
                >
                    <Input id="lot_number" v-model="form.lot_number" required />
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
                    label="Fecha de fabricación"
                    for="manufacture_date"
                    :error="form.errors.manufacture_date"
                >
                    <Input
                        id="manufacture_date"
                        v-model="form.manufacture_date"
                        type="date"
                    />
                </FormField>
                <FormField
                    label="Fecha de caducidad"
                    for="expiration_date"
                    :error="form.errors.expiration_date"
                >
                    <Input
                        id="expiration_date"
                        v-model="form.expiration_date"
                        type="date"
                    />
                </FormField>
                <FormField
                    label="Fecha de recepción"
                    for="received_at"
                    :error="form.errors.received_at"
                >
                    <Input
                        id="received_at"
                        v-model="form.received_at"
                        type="date"
                    />
                </FormField>
                <FormField
                    label="Costo"
                    for="cost"
                    required
                    :error="form.errors.cost"
                >
                    <Input
                        id="cost"
                        v-model="form.cost"
                        type="number"
                        step="0.0001"
                        min="0"
                        required
                    />
                </FormField>
            </div>

            <Button type="submit" :disabled="form.processing"
                >Guardar cambios</Button
            >
        </form>
    </div>
</template>
