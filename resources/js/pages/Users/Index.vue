<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, Trash2Icon, UsersIcon } from '@lucide/vue';
import UserController from '@/actions/App/Http/Controllers/UserController';
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterBar from '@/components/filters/FilterBar.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { create, index } from '@/routes/users';
import type { ManagedUser, Paginated } from '@/types';

const props = defineProps<{
    users: Paginated<ManagedUser>;
    filters: { search?: string };
}>();

const { can, user: currentUser } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: index() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Correo' },
    { key: 'role', label: 'Rol' },
    { key: 'is_active', label: 'Estado' },
    { key: 'actions', label: '', class: 'text-right' },
];

function destroy(user: ManagedUser) {
    router.delete(UserController.destroy.url(user.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Usuarios"
            description="Colaboradores con acceso al sistema."
        >
            <template #actions>
                <Button v-if="can('users.manage')" as-child>
                    <Link :href="create()">
                        <PlusIcon />
                        Nuevo usuario
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <FilterBar
            :model-value="filters.search ?? ''"
            placeholder="Buscar por nombre o correo..."
        />

        <ServerDataTable :columns="columns" :paginated="props.users">
            <template #empty>
                <EmptyState
                    :icon="UsersIcon"
                    title="Sin usuarios"
                    description="Invita a tu equipo creando su primer usuario."
                />
            </template>
            <template #cell-role="{ row }">
                <Badge variant="outline">{{ row.role ?? 'Sin rol' }}</Badge>
            </template>
            <template #cell-is_active="{ row }">
                <Badge :variant="row.is_active ? 'default' : 'secondary'">
                    {{ row.is_active ? 'Activo' : 'Inactivo' }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Tooltip v-if="can('users.manage')">
                        <TooltipTrigger as-child>
                            <Button
                                as-child
                                size="icon"
                                variant="ghost"
                                aria-label="Editar usuario"
                            >
                                <Link :href="UserController.edit.url(row.id)">
                                    <PencilIcon />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Editar usuario</TooltipContent>
                    </Tooltip>
                    <ConfirmationDialog
                        v-if="can('users.manage') && row.id !== currentUser?.id"
                        title="¿Desactivar usuario?"
                        :description="`«${row.name}» perderá acceso al sistema. Podrás reactivarlo restaurando su registro.`"
                        tooltip="Desactivar usuario"
                        @confirm="destroy(row)"
                    >
                        <template #trigger>
                            <Button
                                size="icon"
                                variant="ghost"
                                aria-label="Desactivar usuario"
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
