<script setup lang="ts">
import { XIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';

export type ActiveFilter = {
    key: string;
    label: string;
    value: string;
};

defineProps<{
    filters: ActiveFilter[];
}>();

const emit = defineEmits<{ remove: [key: string]; clearAll: [] }>();
</script>

<template>
    <div v-if="filters.length" class="flex flex-wrap items-center gap-2">
        <span
            v-for="filter in filters"
            :key="filter.key"
            class="inline-flex items-center gap-1 rounded-full border bg-muted/50 py-1 pr-1 pl-2.5 text-xs"
        >
            <span class="text-muted-foreground">{{ filter.label }}:</span>
            <span class="font-medium">{{ filter.value }}</span>
            <button
                type="button"
                class="rounded-full p-0.5 text-muted-foreground hover:bg-accent hover:text-foreground"
                :title="`Quitar filtro de ${filter.label}`"
                @click="emit('remove', filter.key)"
            >
                <XIcon class="size-3" />
            </button>
        </span>
        <Button
            v-if="filters.length > 1"
            variant="ghost"
            size="xs"
            class="text-muted-foreground"
            @click="emit('clearAll')"
        >
            Limpiar filtros
        </Button>
    </div>
</template>
