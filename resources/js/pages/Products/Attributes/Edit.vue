<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/products/attributes';
import type { ProductAttribute } from '@/types';
import AttributeForm from './AttributeForm.vue';

const props = defineProps<{
    attribute: ProductAttribute;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Atributos de variantes', href: index() },
            { title: 'Editar atributo', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar atributo" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.attribute.name}»`"
            description="Actualiza el atributo y sus valores."
        />
        <AttributeForm :attribute="props.attribute" />
    </div>
</template>
