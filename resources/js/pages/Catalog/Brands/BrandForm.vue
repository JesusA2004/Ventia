<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BrandController from '@/actions/App/Http/Controllers/Catalog/BrandController';
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
import type { Brand } from '@/types';

const props = defineProps<{
    brand?: Brand;
}>();

const form = useForm({
    name: props.brand?.name ?? '',
    description: props.brand?.description ?? '',
    status: props.brand?.status ?? 'active',
});

function submit() {
    if (props.brand) {
        form.put(BrandController.update.url(props.brand.id));
    } else {
        form.post(BrandController.store.url());
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
                <Input id="name" v-model="form.name" required autofocus />
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
                label="Descripción"
                for="description"
                class="sm:col-span-2"
                :error="form.errors.description"
            >
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                />
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ brand ? 'Guardar cambios' : 'Crear marca' }}
        </Button>
    </form>
</template>
