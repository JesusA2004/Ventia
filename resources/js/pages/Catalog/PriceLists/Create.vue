<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/price-lists';
import PriceListForm from './PriceListForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Listas de precios', href: index() },
            { title: 'Nueva lista', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva lista de precios" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva lista de precios"
            description="Crea una nueva lista de precios."
        />
        <PriceListForm />
    </div>
</template>
