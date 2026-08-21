<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/promotions/promotions';
import type { Promotion, PromotionScopeOption } from '@/types';
import PromotionForm from './PromotionForm.vue';

defineProps<{
    promotion: Promotion;
    branchOptions: PromotionScopeOption[];
    categoryOptions: PromotionScopeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Promociones', href: index() },
            { title: 'Editar promoción', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Editar ${promotion.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar ${promotion.name}`"
            description="Modifica las condiciones de esta promoción."
        />
        <PromotionForm
            :promotion="promotion"
            :branch-options="branchOptions"
            :category-options="categoryOptions"
        />
    </div>
</template>
