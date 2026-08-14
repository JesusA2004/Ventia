<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/products';
import type {
    Brand,
    Category,
    Product,
    ProductAttribute,
    ProductStockBalance,
    Tax,
    Unit,
} from '@/types';
import ProductForm from './ProductForm.vue';

const props = defineProps<{
    product: Product;
    categoryOptions: Category[];
    brandOptions: Brand[];
    unitOptions: Unit[];
    taxOptions: Tax[];
    attributeOptions: ProductAttribute[];
    stockBalances: ProductStockBalance[];
    totalStock: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: index() },
            { title: 'Editar producto', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Editar producto" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="`Editar «${props.product.name}»`"
            description="Actualiza los datos del producto."
        />
        <ProductForm
            :product="props.product"
            :category-options="categoryOptions"
            :brand-options="brandOptions"
            :unit-options="unitOptions"
            :tax-options="taxOptions"
            :attribute-options="attributeOptions"
            :stock-balances="stockBalances"
            :total-stock="totalStock"
        />
    </div>
</template>
