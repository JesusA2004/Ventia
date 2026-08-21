<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { auditActionVariant } from '@/lib/auditBadges';
import { formatDateTime } from '@/lib/format';
import { index as auditIndex } from '@/routes/audit';
import type { AuditLog } from '@/types';

const props = defineProps<{
    log: AuditLog;
}>();

const showJson = ref(false);

type DiffRow = { field: string; before: unknown; after: unknown };

function display(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Sí' : 'No';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

const diffRows = computed<DiffRow[]>(() => {
    const before = props.log.old_values ?? {};
    const after = props.log.new_values ?? {};
    const fields = new Set([...Object.keys(before), ...Object.keys(after)]);

    return Array.from(fields)
        .filter((field) => display(before[field]) !== display(after[field]))
        .map((field) => ({
            field,
            before: before[field],
            after: after[field],
        }));
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Auditoría', href: auditIndex() },
            { title: 'Detalle', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Detalle de auditoría" />

    <div class="flex max-w-3xl flex-col gap-6">
        <PageHeader
            title="Detalle del evento"
            :description="formatDateTime(log.created_at)"
        >
            <template #actions>
                <Badge :variant="auditActionVariant(log.action)">{{
                    log.action_label
                }}</Badge>
            </template>
        </PageHeader>

        <div class="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
            <div>
                <p class="text-xs text-muted-foreground">Usuario</p>
                <p class="text-sm font-medium">{{ log.user_name }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Módulo</p>
                <Badge variant="outline">{{ log.module_label }}</Badge>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Empresa</p>
                <p class="text-sm">{{ log.company_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Sucursal</p>
                <p class="text-sm">{{ log.branch_name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Acción</p>
                <p class="text-sm">{{ log.action_label }}</p>
                <p class="text-xs text-muted-foreground">{{ log.action }}</p>
            </div>
            <div v-if="log.entity_type">
                <p class="text-xs text-muted-foreground">Entidad</p>
                <p class="text-sm">
                    {{ log.entity_type }}
                    <span v-if="log.entity_id" class="text-muted-foreground"
                        >#{{ log.entity_id }}</span
                    >
                </p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs text-muted-foreground">Descripción</p>
                <p class="text-sm">{{ log.description }}</p>
            </div>
            <div v-if="log.reason" class="sm:col-span-2">
                <p class="text-xs text-muted-foreground">Motivo</p>
                <p class="text-sm">{{ log.reason }}</p>
            </div>
            <div v-if="log.ip_address">
                <p class="text-xs text-muted-foreground">Dirección IP</p>
                <p class="text-sm">{{ log.ip_address }}</p>
            </div>
            <div v-if="log.user_agent" class="sm:col-span-2">
                <p class="text-xs text-muted-foreground">Dispositivo</p>
                <p class="line-clamp-2 text-xs text-muted-foreground">
                    {{ log.user_agent }}
                </p>
            </div>
        </div>

        <div v-if="diffRows.length > 0" class="space-y-2">
            <h3 class="text-sm font-medium">Cambios</h3>
            <div class="overflow-hidden rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">
                                Campo
                            </th>
                            <th class="px-3 py-2 text-left font-medium">
                                Antes
                            </th>
                            <th class="px-3 py-2 text-left font-medium">
                                Después
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in diffRows" :key="row.field">
                            <td class="px-3 py-2 font-medium">
                                {{ row.field }}
                            </td>
                            <td class="px-3 py-2 text-muted-foreground">
                                {{ display(row.before) }}
                            </td>
                            <td class="px-3 py-2 font-medium text-foreground">
                                {{ display(row.after) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="log.old_values || log.new_values" class="space-y-2">
            <Button variant="ghost" size="sm" @click="showJson = !showJson">
                {{ showJson ? 'Ocultar' : 'Ver' }} JSON técnico
            </Button>
            <div v-if="showJson" class="grid gap-4 sm:grid-cols-2">
                <div v-if="log.old_values">
                    <p class="mb-1 text-xs text-muted-foreground">
                        Valores anteriores
                    </p>
                    <pre
                        class="overflow-x-auto rounded-lg border bg-muted/40 p-3 text-xs"
                        >{{ JSON.stringify(log.old_values, null, 2) }}</pre>
                </div>
                <div v-if="log.new_values">
                    <p class="mb-1 text-xs text-muted-foreground">
                        Valores nuevos
                    </p>
                    <pre
                        class="overflow-x-auto rounded-lg border bg-muted/40 p-3 text-xs"
                        >{{ JSON.stringify(log.new_values, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>
