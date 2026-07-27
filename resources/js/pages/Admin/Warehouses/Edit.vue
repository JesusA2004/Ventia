<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/settings/warehouses';
import type { Branch, Warehouse } from '@/types';
import WarehouseForm from './WarehouseForm.vue';

const props = defineProps<{
    warehouse: Warehouse;
    branches: Branch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Almacenes', href: index() },
            { title: 'Editar almacén', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar almacén" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.warehouse.name}»`"
            description="Actualiza los datos del almacén."
        />
        <WarehouseForm
            :warehouse="props.warehouse"
            :branches="props.branches"
        />
    </div>
</template>
