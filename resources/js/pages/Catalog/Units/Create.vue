<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/units';
import type { Unit } from '@/types';
import UnitForm from './UnitForm.vue';

defineProps<{
    baseUnitOptions: Unit[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Unidades', href: index() },
            { title: 'Nueva unidad', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nueva unidad" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nueva unidad"
            description="Registra una nueva unidad de medida."
        />
        <UnitForm :base-unit-options="baseUnitOptions" />
    </div>
</template>
