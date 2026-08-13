<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/settings/branches';
import BranchForm from './BranchForm.vue';

defineProps<{
    suggestedCode: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sucursales', href: index() },
            { title: 'Nueva sucursal', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva sucursal" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva sucursal"
            description="Registra una nueva sucursal de tu empresa."
        />
        <BranchForm :suggested-code="suggestedCode" />
    </div>
</template>
