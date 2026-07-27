<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/units';
import type { Unit } from '@/types';
import UnitForm from './UnitForm.vue';

const props = defineProps<{
    unit: Unit;
    baseUnitOptions: Unit[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Unidades', href: index() },
            { title: 'Editar unidad', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar unidad" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.unit.name}»`"
            description="Actualiza los datos de la unidad."
        />
        <UnitForm :unit="props.unit" :base-unit-options="baseUnitOptions" />
    </div>
</template>
