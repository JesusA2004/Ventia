<script setup lang="ts" generic="T extends object">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

export type DataTableColumn = {
    key: string;
    label: string;
    class?: string;
};

defineProps<{
    columns: DataTableColumn[];
    paginated: Paginated<T>;
}>();

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
                    <TableRow v-if="paginated.data.length === 0">
                        <TableCell :colspan="columns.length" class="p-0">
                            <slot name="empty" />
                        </TableCell>
                    </TableRow>
                    <TableRow
                        v-for="(row, index) in paginated.data"
                        :key="index"
                    >
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

        <div
            v-if="paginated.data.length > 0"
            class="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p class="text-sm text-muted-foreground">
                Mostrando {{ paginated.meta.from }}–{{ paginated.meta.to }} de
                {{ paginated.meta.total }}
            </p>
            <div class="flex flex-wrap items-center gap-1">
                <Button
                    v-for="(link, index) in paginated.links"
                    :key="index"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    :class="cn(!link.url && 'pointer-events-none opacity-50')"
                >
                    <Link v-if="link.url" :href="link.url" preserve-scroll>
                        <span v-html="link.label" />
                    </Link>
                    <span v-else v-html="link.label" />
                </Button>
            </div>
        </div>
    </div>
</template>
