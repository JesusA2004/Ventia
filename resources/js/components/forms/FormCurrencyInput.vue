<script setup lang="ts">
import FormField from '@/components/forms/FormField.vue';
import { Input } from '@/components/ui/input';

/**
 * Money amounts always use step="0.01" — currency in this system never has
 * more than 2 decimal places, matching resources/js/lib/format.ts's
 * formatCurrency() rule.
 */
withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        id?: string;
        error?: string;
        help?: string;
        required?: boolean;
        disabled?: boolean;
        min?: string;
        placeholder?: string;
        currencySymbol?: string;
    }>(),
    {
        id: undefined,
        error: undefined,
        help: undefined,
        required: false,
        disabled: false,
        min: '0',
        placeholder: '0.00',
        currencySymbol: '$',
    },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();
</script>

<template>
    <FormField :label="label" :for="id" :error="error" :required="required">
        <div class="relative">
            <span
                class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground"
            >
                {{ currencySymbol }}
            </span>
            <Input
                :id="id"
                :model-value="modelValue"
                type="number"
                :min="min"
                step="0.01"
                :placeholder="placeholder"
                :disabled="disabled"
                class="pl-6"
                @update:model-value="emit('update:modelValue', String($event))"
            />
        </div>
        <p v-if="help" class="text-xs text-muted-foreground">{{ help }}</p>
    </FormField>
</template>
