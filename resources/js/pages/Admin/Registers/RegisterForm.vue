<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import RegisterController from '@/actions/App/Http/Controllers/Settings/RegisterController';
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
import type { Branch, CashRegister, ManagedUser } from '@/types';

const props = defineProps<{
    register?: CashRegister;
    branches: Branch[];
    users: ManagedUser[];
}>();

const form = useForm({
    branch_id: props.register?.branch_id ?? props.branches[0]?.id ?? null,
    name: props.register?.name ?? '',
    code: props.register?.code ?? '',
    printer_name: props.register?.printer_name ?? '',
    has_cash_drawer: props.register?.has_cash_drawer ?? true,
    assigned_user_id: props.register?.assigned_user_id ?? null,
    status: props.register?.status ?? 'active',
});

function submit() {
    if (props.register) {
        form.put(RegisterController.update.url(props.register.id));
    } else {
        form.post(RegisterController.store.url());
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
            <FormField
                label="Impresora"
                for="printer_name"
                :error="form.errors.printer_name"
            >
                <Input
                    id="printer_name"
                    v-model="form.printer_name"
                    placeholder="Térmica 80mm"
                />
            </FormField>
            <FormField
                label="Usuario asignado"
                for="assigned_user_id"
                :error="form.errors.assigned_user_id"
            >
                <Select v-model="form.assigned_user_id">
                    <SelectTrigger id="assigned_user_id" class="w-full">
                        <SelectValue placeholder="Sin asignar" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="user in users"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
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
                label="Cajón de dinero"
                for="has_cash_drawer"
                :error="form.errors.has_cash_drawer"
            >
                <div class="flex h-9 items-center">
                    <Switch
                        id="has_cash_drawer"
                        v-model="form.has_cash_drawer"
                    />
                </div>
            </FormField>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ register ? 'Guardar cambios' : 'Crear caja' }}
        </Button>
    </form>
</template>
