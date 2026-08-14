<script setup lang="ts">
import { computed } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Input } from '@/components/ui/input';
import { isValidHex } from '@/lib/color';

/**
 * Graphical color picker synced with a hex text field, so the user can
 * either click a swatch or type/paste a known hex value — either updates
 * the other. The native `<input type="color">` always needs a valid 6-digit
 * hex to render, so it falls back to a neutral gray while the text field is
 * empty or mid-edit rather than crashing on an invalid value.
 */
const props = withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        id?: string;
        error?: string;
        tooltip?: string;
        required?: boolean;
    }>(),
    {
        id: undefined,
        error: undefined,
        tooltip: undefined,
        required: false,
    },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();

const swatchValue = computed(() =>
    isValidHex(props.modelValue) ? props.modelValue : '#94a3b8',
);

function onSwatchInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
}

function onTextInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
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
        <div class="flex items-center gap-2">
            <input
                :id="id"
                type="color"
                :value="swatchValue"
                class="size-9 shrink-0 cursor-pointer rounded-md border bg-background p-1"
                :aria-label="`Seleccionar ${label.toLowerCase()}`"
                @input="onSwatchInput"
            />
            <Input
                :model-value="modelValue"
                placeholder="#0EA5A4"
                maxlength="7"
                class="font-mono uppercase"
                @input="onTextInput"
            />
        </div>
    </FormField>
</template>
