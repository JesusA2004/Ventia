<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/products/attributes';
import AttributeForm from './AttributeForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Atributos de variantes', href: index() },
            { title: 'Nuevo atributo', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo atributo" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo atributo"
            description="Crea un atributo (Talla, Color, Sabor...) y sus valores."
        />
        <AttributeForm />
    </div>
</template>
