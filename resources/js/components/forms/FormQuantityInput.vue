<script setup lang="ts">
import FormField from '@/components/forms/FormField.vue';
import { Input } from '@/components/ui/input';

/**
 * step="1" for whole units (piece/box/package), step="0.01" for fractionable
 * units (kg, liter, etc.) — mirrors formatQuantity()'s 2-decimal cap in
 * resources/js/lib/format.ts, so what a user can type always matches what
 * gets displayed back.
 */
withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        allowsFraction: boolean;
        id?: string;
        error?: string;
        help?: string;
        tooltip?: string;
        required?: boolean;
        disabled?: boolean;
        min?: string;
        unitSymbol?: string;
    }>(),
    {
        id: undefined,
        error: undefined,
        help: undefined,
        tooltip: undefined,
        required: false,
        disabled: false,
        min: '0',
        unitSymbol: undefined,
    },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();
</script>

<template>
    <FormField
        :label="label"
        :for="id"
        :error="error"
        :required="required"
        :tooltip="tooltip"
    >
        <div class="relative">
            <Input
                :id="id"
                :model-value="modelValue"
                type="number"
                :min="min"
                :step="allowsFraction ? '0.01' : '1'"
                :disabled="disabled"
                :class="unitSymbol ? 'pr-12' : ''"
                @update:model-value="emit('update:modelValue', String($event))"
            />
            <span
                v-if="unitSymbol"
                class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm text-muted-foreground"
            >
                {{ unitSymbol }}
            </span>
        </div>
        <p v-if="help" class="text-xs text-muted-foreground">{{ help }}</p>
    </FormField>
</template>
