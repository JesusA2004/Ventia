<script setup lang="ts">
import FormField from '@/components/forms/FormField.vue';
import { Input } from '@/components/ui/input';

/**
 * step="1" keeps the spinner arrows incrementing by whole currency units
 * instead of $0.01 at a time — the user can still type exact cents
 * manually (10, 10.5, 10.50, 1000.25...). Money never shows more than 2
 * decimals, matching resources/js/lib/format.ts's formatCurrency() rule;
 * normalizeAmount() below caps what round-trips through the field on blur,
 * even though the DB may keep more precision (DECIMAL(14,4)).
 */
withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        id?: string;
        error?: string;
        help?: string;
        tooltip?: string;
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
        tooltip: undefined,
        required: false,
        disabled: false,
        min: '0',
        placeholder: '0.00',
        currencySymbol: '$',
    },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();

function normalizeAmount(event: FocusEvent) {
    const target = event.target as HTMLInputElement;

    if (target.value === '' || Number.isNaN(Number(target.value))) {
        return;
    }

    emit('update:modelValue', Number(target.value).toFixed(2));
}
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
            <span
                class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground"
            >
                {{ currencySymbol }}
            </span>
            <Input
                :id="id"
                :model-value="modelValue"
                type="number"
                inputmode="decimal"
                :min="min"
                step="1"
                :placeholder="placeholder"
                :disabled="disabled"
                class="pl-6"
                @update:model-value="emit('update:modelValue', String($event))"
                @blur="normalizeAmount"
            />
        </div>
        <p v-if="help" class="text-xs text-muted-foreground">{{ help }}</p>
    </FormField>
</template>
