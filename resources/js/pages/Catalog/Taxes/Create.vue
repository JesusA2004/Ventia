<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/taxes';
import TaxForm from './TaxForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Impuestos', href: index() },
            { title: 'Nuevo impuesto', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo impuesto" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo impuesto"
            description="Registra un nuevo impuesto."
        />
        <TaxForm />
    </div>
</template>
