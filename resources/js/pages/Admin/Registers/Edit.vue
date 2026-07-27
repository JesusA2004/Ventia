<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/settings/registers';
import type { Branch, CashRegister, ManagedUser } from '@/types';
import RegisterForm from './RegisterForm.vue';

const props = defineProps<{
    register: CashRegister;
    branches: Branch[];
    users: ManagedUser[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cajas', href: index() },
            { title: 'Editar caja', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar caja" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.register.name}»`"
            description="Actualiza los datos de la caja."
        />
        <RegisterForm
            :register="props.register"
            :branches="props.branches"
            :users="props.users"
        />
    </div>
</template>
