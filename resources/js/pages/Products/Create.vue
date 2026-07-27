<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/products';
import type { Brand, Category, ProductAttribute, Tax, Unit } from '@/types';
import ProductForm from './ProductForm.vue';

defineProps<{
    categoryOptions: Category[];
    brandOptions: Brand[];
    unitOptions: Unit[];
    taxOptions: Tax[];
    attributeOptions: ProductAttribute[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: index() },
            { title: 'Nuevo producto', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo producto" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo producto"
            description="Registra un nuevo producto en el catálogo."
        />
        <ProductForm
            :category-options="categoryOptions"
            :brand-options="brandOptions"
            :unit-options="unitOptions"
            :tax-options="taxOptions"
            :attribute-options="attributeOptions"
        />
    </div>
</template>
