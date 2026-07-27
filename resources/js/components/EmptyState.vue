<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

type Props = {
    icon?: LucideIcon;
    title: string;
    description?: string;
};

defineProps<Props>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-12 text-center"
    >
        <component
            :is="icon"
            v-if="icon"
            class="mb-2 size-8 text-muted-foreground"
        />
        <p class="text-sm font-medium">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-2">
            <slot name="action" />
        </div>
    </div>
</template>
