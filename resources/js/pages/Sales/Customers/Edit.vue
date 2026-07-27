<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/customers';
import type { Customer, PriceList } from '@/types';
import CustomerForm from './CustomerForm.vue';

defineProps<{
    customer: Customer;
    priceListOptions: PriceList[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clientes', href: index() },
            { title: 'Editar cliente', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar cliente" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Editar cliente"
            :description="`Actualiza los datos de ${customer.name}.`"
        />
        <CustomerForm
            :customer="customer"
            :price-list-options="priceListOptions"
        />
    </div>
</template>
