<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageIcon, PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import BrandController from '@/actions/App/Http/Controllers/Catalog/BrandController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/catalog/brands';
import type { Brand, Paginated } from '@/types';

const props = defineProps<{
    brands: Paginated<Brand>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Marcas', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'products_count', label: 'Productos' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(brand: Brand) {
    router.delete(BrandController.destroy.url(brand.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Marcas" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Marcas"
            description="Marcas de los productos de tu catálogo."
        >
            <template #actions>
                <Button v-if="can('brands.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva marca
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre..."
        />

        <ServerDataTable :columns="columns" :paginated="props.brands">
            <template #empty>
                <EmptyState
                    :icon="PackageIcon"
                    title="Sin marcas"
                    description="Crea tu primera marca."
                />
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        v-if="can('brands.manage')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="BrandController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('brands.manage')"
                        title="¿Eliminar marca?"
                        :description="`No podrás eliminar «${row.name}» si tiene productos relacionados.`"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button size="icon" variant="ghost">
                                <Trash2Icon />
                            </Button>
                        </template>
                    </ConfirmationDialog>
                </div>
            </template>
        </ServerDataTable>
    </div>
</template>
