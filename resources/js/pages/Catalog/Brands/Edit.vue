<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/brands';
import type { Brand } from '@/types';
import BrandForm from './BrandForm.vue';

const props = defineProps<{
    brand: Brand;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Marcas', href: index() },
            { title: 'Editar marca', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar marca" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.brand.name}»`"
            description="Actualiza los datos de la marca."
        />
        <BrandForm :brand="props.brand" />
    </div>
</template>
