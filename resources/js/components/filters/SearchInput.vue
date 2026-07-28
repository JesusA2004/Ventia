<script setup lang="ts">
import { SearchIcon, XIcon } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';

/**
 * Debounced (300ms), URL-syncing search box. Never has a "Buscar" button —
 * it updates as the user types and never triggers a full page reload.
 */
const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        debounce?: number;
        class?: string;
    }>(),
    {
        placeholder: 'Buscar...',
        debounce: 300,
        class: undefined,
    },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();

const search = ref(props.modelValue);

watchDebounced(search, (value) => emit('update:modelValue', value), {
    debounce: props.debounce,
});

function clear() {
    search.value = '';
    emit('update:modelValue', '');
}
</script>

<template>
    <div :class="['relative w-full sm:max-w-xs', props.class]">
        <SearchIcon
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input v-model="search" :placeholder="placeholder" class="pr-8 pl-9" />
        <button
            v-if="search"
            type="button"
            class="absolute top-1/2 right-2 -translate-y-1/2 rounded-sm p-0.5 text-muted-foreground hover:bg-accent hover:text-foreground"
            title="Limpiar búsqueda"
            @click="clear"
        >
            <XIcon class="size-3.5" />
        </button>
    </div>
</template>
