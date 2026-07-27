<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/categories';
import CategoryForm from './CategoryForm.vue';

defineProps<{
    parentOptions: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categorías', href: index() },
            { title: 'Nueva categoría', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva categoría" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva categoría"
            description="Crea una categoría o subcategoría de productos."
        />
        <CategoryForm :parent-options="parentOptions" />
    </div>
</template>
