<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/payment-methods';
import type { PaymentMethod } from '@/types';
import PaymentMethodForm from './PaymentMethodForm.vue';

defineProps<{
    paymentMethod: PaymentMethod;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Métodos de pago', href: index() },
            { title: 'Editar método de pago', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar método de pago" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Editar método de pago"
            :description="`Actualiza «${paymentMethod.name}».`"
        />
        <PaymentMethodForm :payment-method="paymentMethod" />
    </div>
</template>
