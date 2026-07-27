<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/brands';
import BrandForm from './BrandForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Marcas', href: index() },
            { title: 'Nueva marca', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva marca" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva marca"
            description="Registra una nueva marca."
        />
        <BrandForm />
    </div>
</template>
