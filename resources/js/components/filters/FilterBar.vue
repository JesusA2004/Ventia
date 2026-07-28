<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import SearchInput from '@/components/filters/SearchInput.vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

function onSearch(value: string) {
    router.get(
        window.location.pathname,
        { search: value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <SearchInput
            :model-value="props.modelValue"
            :placeholder="placeholder"
            @update:model-value="onSearch"
        />
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
