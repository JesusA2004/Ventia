<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CircleHelpIcon } from '@lucide/vue';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { index } from '@/routes/roles';
import type { PermissionGroup } from '@/types';

const props = defineProps<{
    role: { id: number; name: string };
    permissionGroups: PermissionGroup[];
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
    props.permissionGroups
        .flatMap((group) => group.permissions)
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
                v-for="group in permissionGroups"
                :key="group.label"
                class="space-y-3"
            >
                <h3 class="text-sm font-medium">{{ group.label }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="permission in group.permissions"
                        :key="permission.name"
                        class="flex items-center gap-1.5"
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
                            permission.label
                        }}</Label>
                        <Tooltip v-if="permission.description">
                            <TooltipTrigger
                                type="button"
                                class="text-muted-foreground hover:text-foreground"
                                :aria-label="`Ayuda: ${permission.label}`"
                            >
                                <CircleHelpIcon class="size-3.5" />
                            </TooltipTrigger>
                            <TooltipContent>{{
                                permission.description
                            }}</TooltipContent>
                        </Tooltip>
                    </div>
                </div>
            </div>

            <Button type="submit" :disabled="form.processing"
                >Guardar permisos</Button
            >
        </form>
    </div>
</template>
