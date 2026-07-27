<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const search = ref(props.modelValue);

watchDebounced(
    search,
    (value) => {
        router.get(
            window.location.pathname,
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
    { debounce: 350 },
);
</script>

<template>
    <div class="relative w-full sm:max-w-xs">
        <SearchIcon
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            v-model="search"
            :placeholder="placeholder ?? 'Buscar...'"
            class="pl-9"
        />
    </div>
</template>
