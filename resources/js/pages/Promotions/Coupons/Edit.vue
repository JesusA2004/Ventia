<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/promotions/coupons';
import type { Coupon, PromotionScopeOption } from '@/types';
import CouponForm from './CouponForm.vue';

defineProps<{
    coupon: Coupon;
    branchOptions: PromotionScopeOption[];
    categoryOptions: PromotionScopeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Cupones', href: index() },
            { title: 'Editar cupón', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Editar ${coupon.code}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar ${coupon.code}`"
            description="Modifica las condiciones de este cupón."
        />
        <CouponForm
            :coupon="coupon"
            :branch-options="branchOptions"
            :category-options="categoryOptions"
        />
    </div>
</template>
