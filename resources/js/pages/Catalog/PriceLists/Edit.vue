<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/catalog/price-lists';
import type { PriceList } from '@/types';
import PriceListForm from './PriceListForm.vue';

const props = defineProps<{
    priceList: PriceList;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Listas de precios', href: index() },
            { title: 'Editar lista', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar lista de precios" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.priceList.name}»`"
            description="Actualiza los datos de la lista de precios."
        />
        <PriceListForm :price-list="props.priceList" />
    </div>
</template>
