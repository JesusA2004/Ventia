<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { PlusIcon, XIcon } from '@lucide/vue';
import ProductAttributeController from '@/actions/App/Http/Controllers/Catalog/ProductAttributeController';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { ProductAttribute } from '@/types';

const props = defineProps<{
    attribute?: ProductAttribute;
}>();

const initialValues: { id?: number; value: string }[] = props.attribute
    ? props.attribute.values.map((value) => ({
          id: value.id,
          value: value.value,
      }))
    : [{ value: '' }];

const form = useForm({
    name: props.attribute?.name ?? '',
    status: props.attribute?.status ?? 'active',
    values: initialValues,
});

function addValue() {
    form.values.push({ value: '' });
}

function removeValue(index: number) {
    form.values.splice(index, 1);
}

function submit() {
    if (props.attribute) {
        form.put(ProductAttributeController.update.url(props.attribute.id));
    } else {
        form.post(ProductAttributeController.store.url());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Nombre del atributo"
                for="name"
                required
                :error="form.errors.name"
                tooltip="Característica que distingue las variantes de un producto (por ejemplo, Talla, Color, Sabor)."
            >
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    autofocus
                    placeholder="Talla, Color, Sabor..."
                />
            </FormField>
            <FormField
                label="Estado"
                for="status"
                required
                :error="form.errors.status"
                tooltip="Un atributo inactivo deja de estar disponible para generar nuevas variantes de producto."
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
        </div>

        <div class="space-y-2">
            <p class="text-sm font-medium">Valores</p>
            <div
                v-for="(value, index) in form.values"
                :key="index"
                class="flex items-center gap-2"
            >
                <Input
                    v-model="value.value"
                    placeholder="Chica, Rojo, Chocolate..."
                />
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Quitar valor"
                            @click="removeValue(index)"
                        >
                            <XIcon />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Quitar valor</TooltipContent>
                </Tooltip>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addValue">
                <PlusIcon />
                Agregar valor
            </Button>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ attribute ? 'Guardar cambios' : 'Crear atributo' }}
        </Button>
    </form>
</template>
