<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/roles';
import type { PermissionEntry } from '@/types';

const props = defineProps<{
    role: { id: number; name: string };
    permissionGroups: Record<string, PermissionEntry[]>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Roles', href: index() },
            { title: 'Editar rol', href: '#' },
        ],
    },
});

const granted = new Set(
    Object.values(props.permissionGroups)
        .flat()
        .filter((permission) => permission.granted)
        .map((permission) => permission.name),
);

const form = useForm({
    permissions: Array.from(granted),
});

function toggle(name: string, checked: boolean) {
    form.permissions = checked
        ? [...form.permissions, name]
        : form.permissions.filter((permission) => permission !== name);
}

function submit() {
    form.put(RoleController.update.url(props.role.id));
}
</script>

<template>
    <Head :title="`Rol: ${role.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Permisos de «${role.name}»`"
            description="Activa o desactiva los permisos que tendrá este rol."
        />

        <form class="max-w-3xl space-y-8" @submit.prevent="submit">
            <div
                v-for="(permissions, group) in permissionGroups"
                :key="group"
                class="space-y-3"
            >
                <h3 class="text-sm font-medium capitalize">{{ group }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="permission in permissions"
                        :key="permission.name"
                        class="flex items-center gap-2"
                    >
                        <Checkbox
                            :id="permission.name"
                            :model-value="
                                form.permissions.includes(permission.name)
                            "
                            @update:model-value="
                                (checked) =>
                                    toggle(permission.name, checked === true)
                            "
                        />
                        <Label :for="permission.name" class="font-normal">{{
                            permission.name
                        }}</Label>
                    </div>
                </div>
            </div>

            <Button type="submit" :disabled="form.processing"
                >Guardar permisos</Button
            >
        </form>
    </div>
</template>
