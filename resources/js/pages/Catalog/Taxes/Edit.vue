<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/taxes';
import type { Tax } from '@/types';
import TaxForm from './TaxForm.vue';

const props = defineProps<{
    tax: Tax;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Impuestos', href: index() },
            { title: 'Editar impuesto', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar impuesto" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.tax.name}»`"
            description="Actualiza los datos del impuesto."
        />
        <TaxForm :tax="props.tax" />
    </div>
</template>
