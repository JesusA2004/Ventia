<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/users';
import type { Branch } from '@/types';
import UserForm from './UserForm.vue';

defineProps<{
    roles: string[];
    branches: Branch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Usuarios', href: index() },
            { title: 'Nuevo usuario', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo usuario" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo usuario"
            description="Da de alta un nuevo colaborador."
        />
        <UserForm :roles="roles" :branches="branches" />
    </div>
</template>
