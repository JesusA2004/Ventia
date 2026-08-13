<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ScrollTextIcon } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import SearchableSelect from '@/components/forms/SearchableSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { DataTableColumn } from '@/components/tables/ServerDataTable.vue';
import ServerDataTable from '@/components/tables/ServerDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import { index as auditIndex, show as auditShow } from '@/routes/audit';
import type { AuditFilterOption, AuditLog, Branch, Company, Paginated } from '@/types';

const props = defineProps<{
    logs: Paginated<AuditLog>;
    filters: {
        date_from?: string;
        date_to?: string;
        user_id?: number;
        company_id?: number;
        branch_id?: number;
        module?: string;
        action?: string;
        entity_type?: string;
    };
    filterOptions: {
        companies: Company[];
        branches: (Branch & { company_id: number })[];
        users: { id: number; name: string }[];
        modules: AuditFilterOption[];
        actions: string[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Auditoría', href: auditIndex() }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'created_at', label: 'Fecha' },
    { key: 'user_name', label: 'Usuario' },
    { key: 'module_label', label: 'Módulo' },
    { key: 'description', label: 'Descripción' },
    { key: 'company_name', label: 'Empresa' },
    { key: 'actions', label: '', class: 'text-right' },
];

function apply(partial: Record<string, string | number | undefined>) {
    router.get(
        auditIndex.url(),
        { ...props.filters, ...partial },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Auditoría" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Auditoría"
            description="Registro de acciones importantes realizadas por los usuarios del sistema."
        />

        <div class="flex flex-wrap items-end gap-3">
            <div class="grid gap-1.5">
                <label class="text-xs text-muted-foreground">Desde</label>
                <Input
                    type="date"
                    class="w-40"
                    :model-value="filters.date_from ?? ''"
                    @change="
                        (e: Event) =>
                            apply({
                                date_from:
                                    (e.target as HTMLInputElement).value ||
                                    undefined,
                            })
                    "
                />
            </div>
            <div class="grid gap-1.5">
                <label class="text-xs text-muted-foreground">Hasta</label>
                <Input
                    type="date"
                    class="w-40"
                    :model-value="filters.date_to ?? ''"
                    @change="
                        (e: Event) =>
                            apply({
                                date_to:
                                    (e.target as HTMLInputElement).value ||
                                    undefined,
                            })
                    "
                />
            </div>
            <SearchableSelect
                class="w-48"
                :model-value="filters.user_id ? String(filters.user_id) : null"
                :options="
                    filterOptions.users.map((u) => ({
                        value: String(u.id),
                        label: u.name,
                    }))
                "
                placeholder="Usuario"
                all-label="Todos los usuarios"
                @update:model-value="(v) => apply({ user_id: v ?? undefined })"
            />
            <SearchableSelect
                class="w-48"
                :model-value="
                    filters.company_id ? String(filters.company_id) : null
                "
                :options="
                    filterOptions.companies.map((c) => ({
                        value: String(c.id),
                        label: c.name,
                    }))
                "
                placeholder="Empresa"
                all-label="Todas las empresas"
                @update:model-value="
                    (v) => apply({ company_id: v ?? undefined })
                "
            />
            <SearchableSelect
                class="w-48"
                :model-value="
                    filters.branch_id ? String(filters.branch_id) : null
                "
                :options="
                    filterOptions.branches.map((b) => ({
                        value: String(b.id),
                        label: b.name,
                    }))
                "
                placeholder="Sucursal"
                all-label="Todas las sucursales"
                @update:model-value="
                    (v) => apply({ branch_id: v ?? undefined })
                "
            />
            <SearchableSelect
                class="w-48"
                :model-value="filters.module ?? null"
                :options="filterOptions.modules"
                placeholder="Módulo"
                all-label="Todos los módulos"
                @update:model-value="(v) => apply({ module: v ?? undefined })"
            />
            <SearchableSelect
                class="w-44"
                :model-value="filters.action ?? null"
                :options="
                    filterOptions.actions.map((a) => ({ value: a, label: a }))
                "
                placeholder="Tipo de acción"
                all-label="Todas las acciones"
                @update:model-value="(v) => apply({ action: v ?? undefined })"
            />
        </div>

        <ServerDataTable :columns="columns" :paginated="props.logs">
            <template #empty>
                <EmptyState
                    :icon="ScrollTextIcon"
                    title="Sin registros de auditoría"
                    description="No hay actividad registrada con los filtros seleccionados."
                />
            </template>
            <template #cell-created_at="{ row }">
                <span class="text-sm">{{ formatDateTime(row.created_at) }}</span>
            </template>
            <template #cell-module_label="{ row }">
                <Badge variant="outline">{{ row.module_label }}</Badge>
            </template>
            <template #cell-description="{ row }">
                <span class="line-clamp-2 text-sm">{{ row.description }}</span>
            </template>
            <template #cell-company_name="{ row }">
                <span class="text-sm text-muted-foreground">{{
                    row.company_name ?? '—'
                }}</span>
            </template>
            <template #cell-actions="{ row }">
                <Link
                    :href="auditShow.url(row.id)"
                    class="text-sm text-primary hover:underline"
                >
                    Ver detalle
                </Link>
            </template>
        </ServerDataTable>
    </div>
</template>
