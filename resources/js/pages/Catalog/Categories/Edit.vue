<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/categories';
import type { Category } from '@/types';
import CategoryForm from './CategoryForm.vue';

const props = defineProps<{
    category: Category;
    parentOptions: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categorías', href: index() },
            { title: 'Editar categoría', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar categoría" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.category.name}»`"
            description="Actualiza los datos de la categoría."
        />
        <CategoryForm
            :category="props.category"
            :parent-options="parentOptions"
        />
    </div>
</template>
