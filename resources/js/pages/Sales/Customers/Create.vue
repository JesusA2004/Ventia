<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/customers';
import type { PriceList } from '@/types';
import CustomerForm from './CustomerForm.vue';

defineProps<{
    priceListOptions: PriceList[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clientes', href: index() },
            { title: 'Nuevo cliente', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo cliente" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo cliente"
            description="Registra un cliente para usarlo en ventas y en el POS."
        />
        <CustomerForm :price-list-options="priceListOptions" />
    </div>
</template>
