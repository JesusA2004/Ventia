<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { formatDateTime } from '@/lib/format';
import { index as auditIndex } from '@/routes/audit';
import type { AuditLog } from '@/types';

const props = defineProps<{
    log: AuditLog;
}>();

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
        />

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
                <p class="text-sm">{{ log.action }}</p>
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

        <div v-if="log.old_values" class="space-y-2">
            <h3 class="text-sm font-medium">Valores anteriores</h3>
            <pre
                class="overflow-x-auto rounded-lg border bg-muted/40 p-3 text-xs"
                >{{ JSON.stringify(log.old_values, null, 2) }}</pre
            >
        </div>

        <div v-if="log.new_values" class="space-y-2">
            <h3 class="text-sm font-medium">Valores nuevos</h3>
            <pre
                class="overflow-x-auto rounded-lg border bg-muted/40 p-3 text-xs"
                >{{ JSON.stringify(log.new_values, null, 2) }}</pre
            >
        </div>
    </div>
</template>
