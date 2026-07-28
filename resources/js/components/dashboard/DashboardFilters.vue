<script setup lang="ts">
import { RotateCcwIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DateRangePicker from '@/components/filters/DateRangePicker.vue';
import FormCombobox from '@/components/forms/FormCombobox.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';

export type DashboardFiltersValue = {
    preset: string;
    date_from: string;
    date_to: string;
    branch_id: number | null;
    register_id: number | null;
    cashier_id: number | null;
};

const props = defineProps<{
    filters: DashboardFiltersValue;
    branchOptions: { id: number; name: string }[];
    registerOptions: { id: number; name: string }[];
    cashierOptions: { id: number; name: string }[];
    activeCompanyName?: string;
}>();

const emit = defineEmits<{
    update: [partial: Record<string, string | number | undefined>];
    reset: [];
}>();

const lastUpdated = ref(new Date());

watch(
    () => props.filters,
    () => {
        lastUpdated.value = new Date();
    },
    { deep: true },
);

const branchChoices = computed(() =>
    props.branchOptions.map((b) => ({ value: String(b.id), label: b.name })),
);
const registerChoices = computed(() =>
    props.registerOptions.map((r) => ({ value: String(r.id), label: r.name })),
);
const cashierChoices = computed(() =>
    props.cashierOptions.map((c) => ({ value: String(c.id), label: c.name })),
);

const hasActiveFilters = computed(
    () =>
        props.filters.branch_id !== null ||
        props.filters.register_id !== null ||
        props.filters.cashier_id !== null,
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold tracking-tight">Dashboard</h1>
                <Badge v-if="activeCompanyName" variant="secondary">
                    {{ activeCompanyName }}
                </Badge>
            </div>
            <p class="text-xs text-muted-foreground">
                Actualizado: {{ formatDateTime(lastUpdated) }}
            </p>
        </div>
        <p class="text-sm text-muted-foreground">
            Resumen de ventas, caja e inventario del período seleccionado.
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <DateRangePicker
                :model-value="{
                    preset: filters.preset,
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                }"
                @update:model-value="
                    (v) =>
                        emit('update', {
                            preset: v.preset,
                            date_from: v.date_from,
                            date_to: v.date_to,
                        })
                "
            />

            <FormCombobox
                label="Sucursal"
                class="w-44"
                :model-value="
                    filters.branch_id ? String(filters.branch_id) : null
                "
                :options="branchChoices"
                all-label="Todas las sucursales"
                placeholder="Sucursal"
                @update:model-value="
                    (v) => emit('update', { branch_id: v ?? undefined })
                "
            />
            <FormCombobox
                label="Caja"
                class="w-44"
                :model-value="
                    filters.register_id ? String(filters.register_id) : null
                "
                :options="registerChoices"
                all-label="Todas las cajas"
                placeholder="Caja"
                @update:model-value="
                    (v) => emit('update', { register_id: v ?? undefined })
                "
            />
            <FormCombobox
                label="Cajero"
                class="w-44"
                :model-value="
                    filters.cashier_id ? String(filters.cashier_id) : null
                "
                :options="cashierChoices"
                all-label="Todos los cajeros"
                placeholder="Cajero"
                @update:model-value="
                    (v) => emit('update', { cashier_id: v ?? undefined })
                "
            />

            <Button
                v-if="hasActiveFilters"
                variant="ghost"
                size="sm"
                class="text-muted-foreground"
                @click="emit('reset')"
            >
                <RotateCcwIcon class="size-4" />
                Restablecer filtros
            </Button>
        </div>
    </div>
</template>
