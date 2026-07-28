<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';

/**
 * Covers its relatively-positioned parent while a long-running action (e.g.
 * a report export) is in flight. Usage: wrap the content in a
 * `class="relative"` container and render this alongside it with `v-if`.
 */
withDefaults(defineProps<{ message?: string }>(), {
    message: 'Cargando...',
});
</script>

<template>
    <div
        class="absolute inset-0 z-40 flex flex-col items-center justify-center gap-2 rounded-[inherit] bg-background/80 backdrop-blur-[1px]"
    >
        <Spinner class="size-6" />
        <p class="text-sm text-muted-foreground">{{ message }}</p>
    </div>
</template>
