<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/settings/registers';
import type { Branch, ManagedUser } from '@/types';
import RegisterForm from './RegisterForm.vue';

defineProps<{
    branches: Branch[];
    users: ManagedUser[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: index() },
            { title: 'Nueva caja', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva caja" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva caja"
            description="Registra una nueva caja o terminal de cobro."
        />
        <RegisterForm :branches="branches" :users="users" />
    </div>
</template>
