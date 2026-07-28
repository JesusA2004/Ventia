<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CopyIcon,
    PencilIcon,
    PlusIcon,
    ShoppingBagIcon,
    Trash2Icon,
} from '@lucide/vue';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/products';
import type { Brand, Category, Paginated, Product } from '@/types';

const props = defineProps<{
    products: Paginated<Product>;
    filters: {
        search?: string;
        category_id?: string;
        brand_id?: string;
        status?: string;
    };
    categoryOptions: Category[];
    brandOptions: Brand[];
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Productos', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'sku', label: 'SKU' },
    { key: 'category_name', label: 'Categoría' },
    { key: 'sale_price', label: 'Precio' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(product: Product) {
    router.delete(ProductController.destroy.url(product.id), {
        preserveScroll: true,
    });
}

function duplicate(product: Product) {
    router.post(
        ProductController.duplicate.url(product.id),
        {},
        { preserveScroll: true },
    );
}

function filterBy(key: 'category_id' | 'brand_id' | 'status', value: string) {
    router.get(
        index().url,
        { ...props.filters, [key]: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Productos" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Productos"
            description="Catálogo de productos, servicios y variantes."
        >
            <template #actions>
                <Button v-if="can('products.create')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo producto
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-3">
            <FilterBar
                :model-value="filters.search ?? ''"
                placeholder="Buscar por nombre, SKU o código..."
            />

            <Select
                :model-value="filters.category_id ?? ''"
                @update:model-value="
                    (v) => filterBy('category_id', String(v ?? ''))
                "
            >
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Todas las categorías" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todas las categorías</SelectItem>
                    <SelectItem
                        v-for="option in categoryOptions"
                        :key="option.id"
                        :value="String(option.id)"
                    >
                        {{ option.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select
                :model-value="filters.status ?? ''"
                @update:model-value="(v) => filterBy('status', String(v ?? ''))"
            >
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="Todos los estados" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Todos los estados</SelectItem>
                    <SelectItem value="active">Activo</SelectItem>
                    <SelectItem value="inactive">Inactivo</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <ServerDataTable :columns="columns" :paginated="props.products">
            <template #empty>
                <EmptyState
                    :icon="ShoppingBagIcon"
                    title="Sin productos"
                    description="Crea tu primer producto."
                />
            </template>
            <template #cell-category_name="{ row }">
                {{ row.category_name ?? '—' }}
            </template>
            <template #cell-sale_price="{ row }">
                <div class="flex items-center gap-1">
                    {{ row.sale_price }}
                    <Badge v-if="row.is_favorite" variant="outline">★</Badge>
                </div>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip v-if="can('products.create')">
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Duplicar producto"
                                @click="duplicate(row)"
                            >
                                <CopyIcon />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Duplicar producto</TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="can('products.update')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar producto"
                            >
                                <Link
                                    :href="ProductController.edit.url(row.id)"
                                >
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar producto</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="can('products.delete')"
                        title="¿Eliminar producto?"
                        :description="`No podrás eliminar «${row.name}» si tiene movimientos de inventario históricos.`"
                        tooltip="Eliminar producto"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Eliminar producto"
                            >
                                <Trash2Icon />
                            </Button>
                        </template>
                    </ConfirmationDialog>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
