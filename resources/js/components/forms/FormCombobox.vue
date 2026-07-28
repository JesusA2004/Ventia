<script setup lang="ts">
import FormField from '@/components/forms/FormField.vue';
import SearchableSelect from '@/components/forms/SearchableSelect.vue';
import type { SearchableSelectOption } from '@/components/forms/SearchableSelect.vue';

withDefaults(
    defineProps<{
        modelValue: string | null;
        label: string;
        options: SearchableSelectOption[];
        id?: string;
        error?: string;
        help?: string;
        required?: boolean;
        disabled?: boolean;
        loading?: boolean;
        placeholder?: string;
        allowClear?: boolean;
        allLabel?: string;
    }>(),
    {
        id: undefined,
        error: undefined,
        help: undefined,
        required: false,
        disabled: false,
        loading: false,
        placeholder: undefined,
        allowClear: true,
        allLabel: 'Todos',
    },
);

const emit = defineEmits<{ 'update:modelValue': [string | null] }>();
</script>

<template>
    <FormField :label="label" :for="id" :error="error" :required="required">
        <SearchableSelect
            :model-value="modelValue"
            :options="options"
            :placeholder="placeholder ?? label"
            :disabled="disabled"
            :loading="loading"
            :allow-clear="allowClear"
            :all-label="allLabel"
            @update:model-value="emit('update:modelValue', $event)"
        />
        <p v-if="help" class="text-xs text-muted-foreground">{{ help }}</p>
    </FormField>
</template>
