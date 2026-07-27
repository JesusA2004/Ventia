<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PencilIcon, ShieldIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit } from '@/routes/roles';
import { index } from '@/routes/roles';
import type { RoleSummary } from '@/types';

defineProps<{
    roles: RoleSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Roles', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Rol' },
    { key: 'permissions_count', label: 'Permisos' },
    { key: 'users_count', label: 'Usuarios' },
    { key: 'actions', label: '', class: 'text-right' },
];
</script>

<template>
    <Head title="Roles" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Roles"
            description="Roles y permisos disponibles en el sistema."
        />

        <div v-if="roles.length === 0">
            <EmptyState :icon="ShieldIcon" title="Sin roles" />
        </div>
        <div v-else class="overflow-x-auto rounded-lg border">
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
                    <TableRow v-for="role in roles" :key="role.id">
                        <TableCell class="font-medium">{{
                            role.name
                        }}</TableCell>
                        <TableCell>{{ role.permissions_count }}</TableCell>
                        <TableCell>{{ role.users_count }}</TableCell>
                        <TableCell class="text-right">
                            <Badge v-if="!role.editable" variant="outline"
                                >Acceso total</Badge
                            >
                            <Button v-else as-child size="icon" variant="ghost">
                                <Link :href="edit(role.id)">
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
