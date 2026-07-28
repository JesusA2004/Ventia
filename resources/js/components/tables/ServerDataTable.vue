<script setup lang="ts" generic="T extends object">
import { computed } from 'vue';
import Pagination from '@/components/tables/Pagination.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Paginated } from '@/types';

export type DataTableColumn = {
    key: string;
    label: string;
    class?: string;
};

/**
 * Accepts either a paginated listing (paginated) or a plain, already-loaded
 * collection (rows) — e.g. Payment Methods, which the backend never
 * paginates. Passing rows skips the "Mostrando X–Y de Z" footer entirely
 * instead of faking pagination metadata just to satisfy this component.
 */
const props = defineProps<{
    columns: DataTableColumn[];
    paginated?: Paginated<T>;
    rows?: T[];
}>();

const items = computed<T[]>(() => props.rows ?? props.paginated?.data ?? []);

function cellValue(row: T, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-x-auto rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="column in columns"
                            :key="column.key"
                            :class="column.class"
                        >
                            {{ column.label }}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="items.length === 0">
                        <TableCell :colspan="columns.length" class="p-0">
                            <slot name="empty" />
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="(row, index) in items" :key="index">
                        <TableCell
                            v-for="column in columns"
                            :key="column.key"
                            :class="column.class"
                        >
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ cellValue(row, column.key) }}
                            </slot>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <Pagination
            v-if="paginated && items.length > 0"
            :meta="paginated.meta"
            :links="paginated.links"
        />
    </div>
</template>
