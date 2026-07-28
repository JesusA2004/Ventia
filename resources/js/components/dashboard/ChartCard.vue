<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        empty?: boolean;
        loading?: boolean;
        height?: string;
    }>(),
    {
        description: undefined,
        empty: false,
        loading: false,
        height: 'h-64',
    },
);
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <div class="mb-2 flex items-start justify-between gap-2">
            <div>
                <p class="text-sm font-medium">{{ title }}</p>
                <p v-if="description" class="text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-1">
                <slot name="actions" />
            </div>
        </div>

        <Skeleton v-if="loading" :class="height" />
        <div
            v-else-if="empty"
            :class="['flex items-center justify-center', height]"
        >
            <slot name="empty">
                <p class="text-sm text-muted-foreground">
                    Sin datos en el período seleccionado.
                </p>
            </slot>
        </div>
        <div v-else :class="height">
            <slot />
        </div>
    </div>
</template>
