<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/payment-methods';
import PaymentMethodForm from './PaymentMethodForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Métodos de pago', href: index() },
            { title: 'Nuevo método de pago', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo método de pago" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo método de pago"
            description="Configura un método de pago disponible en el POS."
        />
        <PaymentMethodForm />
    </div>
</template>
