<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/settings/branches';
import type { Branch } from '@/types';
import BranchForm from './BranchForm.vue';

const props = defineProps<{
    branch: Branch;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sucursales', href: index() },
            { title: 'Editar sucursal', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar sucursal" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.branch.name}»`"
            description="Actualiza los datos de la sucursal."
        />
        <BranchForm :branch="props.branch" />
    </div>
</template>
