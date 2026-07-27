<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, TagsIcon, Trash2Icon } from '@lucide/vue';
import CategoryController from '@/actions/App/Http/Controllers/Catalog/CategoryController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/catalog/categories';
import type { Category, Paginated } from '@/types';

const props = defineProps<{
    categories: Paginated<Category>;
    filters: { search?: string };
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categorías', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'parent_name', label: 'Categoría padre' },
    { key: 'products_count', label: 'Productos' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(category: Category) {
    router.delete(CategoryController.destroy.url(category.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Categorías" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Categorías"
            description="Organiza tu catálogo en categorías y subcategorías."
        >
            <template #actions>
                <Button v-if="can('categories.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nueva categoría
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre..."
        />

        <ServerDataTable :columns="columns" :paginated="props.categories">
            <template #empty>
                <EmptyState
                    :icon="TagsIcon"
                    title="Sin categorías"
                    description="Crea tu primera categoría de productos."
                />
            </template>
            <template #cell-parent_name="{ row }">
                {{ row.parent_name ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" :label="row.status_label" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        v-if="can('categories.manage')"
                        as-child
                        size="icon"
                        variant="ghost"
                    >
                        <Link :href="CategoryController.edit.url(row.id)">
                            <PencilIcon />
                        </Link>
                    </Button>
                    <ConfirmationDialog
                        v-if="can('categories.manage')"
                        title="¿Eliminar categoría?"
                        :description="`No podrás eliminar «${row.name}» si tiene productos o subcategorías relacionadas.`"
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
