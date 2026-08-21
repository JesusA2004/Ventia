<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/promotions/coupons';
import type { PromotionScopeOption } from '@/types';
import CouponForm from './CouponForm.vue';

defineProps<{
    branchOptions: PromotionScopeOption[];
    categoryOptions: PromotionScopeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cupones', href: index() },
            { title: 'Nuevo cupón', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo cupón" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo cupón"
            description="Crea un código que el cajero pueda capturar en el POS para aplicar un beneficio."
        />
        <CouponForm
            :branch-options="branchOptions"
            :category-options="categoryOptions"
        />
    </div>
</template>
