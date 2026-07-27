<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/users';
import type { Branch, ManagedUser } from '@/types';
import UserForm from './UserForm.vue';

const props = defineProps<{
    targetUser: ManagedUser;
    roles: string[];
    branches: Branch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Usuarios', href: index() },
            { title: 'Editar usuario', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar usuario" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.targetUser.name}»`"
            description="Actualiza los datos del usuario."
        />
        <UserForm
            :target-user="props.targetUser"
            :roles="props.roles"
            :branches="props.branches"
        />
    </div>
</template>
