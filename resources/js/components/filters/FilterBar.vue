<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import SearchInput from '@/components/filters/SearchInput.vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

function onSearch(value: string) {
    // Merge in whatever other filters are already in the URL (e.g. a status
    // select next to this bar via the default slot) so searching doesn't
    // silently clear them — except page, since a new search should land
    // back on page 1.
    const otherFilters = Object.fromEntries(
        new URLSearchParams(window.location.search),
    );
    delete otherFilters.page;

    router.get(
        window.location.pathname,
        { ...otherFilters, search: value || undefined },
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
