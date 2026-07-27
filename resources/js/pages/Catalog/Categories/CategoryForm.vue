<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/Catalog/CategoryController';
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
import type { Category } from '@/types';

const props = defineProps<{
    category?: Category;
    parentOptions: { id: number; name: string }[];
}>();

const form = useForm({
    parent_id: props.category?.parent_id ?? null,
    name: props.category?.name ?? '',
    description: props.category?.description ?? '',
    sort_order: props.category?.sort_order ?? 0,
    status: props.category?.status ?? 'active',
});

function submit() {
    if (props.category) {
        form.put(CategoryController.update.url(props.category.id));
    } else {
        form.post(CategoryController.store.url());
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
                label="Categoría padre"
                for="parent_id"
                :error="form.errors.parent_id"
            >
                <Select v-model="form.parent_id">
                    <SelectTrigger id="parent_id" class="w-full">
                        <SelectValue
                            placeholder="Sin categoría padre (nivel raíz)"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null"
                            >Sin categoría padre</SelectItem
                        >
                        <SelectItem
                            v-for="option in parentOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Orden"
                for="sort_order"
                :error="form.errors.sort_order"
            >
                <Input
                    id="sort_order"
                    v-model.number="form.sort_order"
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
            {{ category ? 'Guardar cambios' : 'Crear categoría' }}
        </Button>
    </form>
</template>
