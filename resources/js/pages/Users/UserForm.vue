<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { Branch, ManagedUser } from '@/types';

const props = defineProps<{
    targetUser?: ManagedUser;
    roles: string[];
    branches: Branch[];
}>();

const form = useForm({
    name: props.targetUser?.name ?? '',
    email: props.targetUser?.email ?? '',
    password: '',
    is_active: props.targetUser?.is_active ?? true,
    role: props.targetUser?.role ?? props.roles[0] ?? '',
    branch_ids: props.targetUser?.branch_ids ?? [],
});

function toggleBranch(branchId: number, checked: boolean) {
    form.branch_ids = checked
        ? [...form.branch_ids, branchId]
        : form.branch_ids.filter((id) => id !== branchId);
}

function submit() {
    if (props.targetUser) {
        form.put(UserController.update.url(props.targetUser.id));
    } else {
        form.post(UserController.store.url());
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
                label="Correo"
                for="email"
                required
                :error="form.errors.email"
            >
                <Input id="email" v-model="form.email" type="email" required />
            </FormField>
            <FormField
                :label="
                    targetUser ? 'Nueva contraseña (opcional)' : 'Contraseña'
                "
                for="password"
                :required="!targetUser"
                :error="form.errors.password"
            >
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    :required="!targetUser"
                />
            </FormField>
            <FormField
                label="Rol"
                for="role"
                required
                :error="form.errors.role"
            >
                <Select v-model="form.role">
                    <SelectTrigger id="role" class="w-full">
                        <SelectValue placeholder="Selecciona un rol" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="role in roles"
                            :key="role"
                            :value="role"
                        >
                            {{ role }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
            <FormField
                label="Activo"
                for="is_active"
                :error="form.errors.is_active"
            >
                <div class="flex h-9 items-center">
                    <Switch id="is_active" v-model="form.is_active" />
                </div>
            </FormField>
        </div>

        <FormField
            label="Sucursales con acceso"
            :error="form.errors.branch_ids"
        >
            <p class="text-xs text-muted-foreground">
                Si no seleccionas ninguna y el rol no tiene acceso a todas las
                sucursales, el usuario no podrá operar.
            </p>
            <div class="grid gap-2 sm:grid-cols-2">
                <div
                    v-for="branch in branches"
                    :key="branch.id"
                    class="flex items-center gap-2"
                >
                    <Checkbox
                        :id="`branch-${branch.id}`"
                        :model-value="form.branch_ids.includes(branch.id)"
                        @update:model-value="
                            (checked) =>
                                toggleBranch(branch.id, checked === true)
                        "
                    />
                    <Label :for="`branch-${branch.id}`" class="font-normal">{{
                        branch.name
                    }}</Label>
                </div>
            </div>
        </FormField>

        <Button type="submit" :disabled="form.processing">
            {{ targetUser ? 'Guardar cambios' : 'Crear usuario' }}
        </Button>
    </form>
</template>
