<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import BranchController from '@/actions/App/Http/Controllers/Settings/BranchController';
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
import type { Branch } from '@/types';

const props = defineProps<{
    branch?: Branch;
}>();

const form = useForm({
    name: props.branch?.name ?? '',
    code: props.branch?.code ?? '',
    address: props.branch?.address ?? '',
    phone: props.branch?.phone ?? '',
    status: props.branch?.status ?? 'active',
});

function submit() {
    if (props.branch) {
        form.put(BranchController.update.url(props.branch.id));
    } else {
        form.post(BranchController.store.url());
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
            <FormField label="Teléfono" for="phone" :error="form.errors.phone">
                <Input id="phone" v-model="form.phone" />
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
                <Input id="address" v-model="form.address" />
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ branch ? 'Guardar cambios' : 'Crear sucursal' }}
        </Button>
    </form>
</template>
