<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/settings/warehouses';
import type { Branch } from '@/types';
import WarehouseForm from './WarehouseForm.vue';

defineProps<{
    branches: Branch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Almacenes', href: index() },
            { title: 'Nuevo almacén', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo almacén" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Nuevo almacén"
            description="Registra un nuevo almacén en una sucursal."
        />
        <WarehouseForm :branches="branches" />
    </div>
</template>
