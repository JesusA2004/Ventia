<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

type Props = {
    for?: string;
    label: string;
    error?: string;
    required?: boolean;
};

defineProps<Props>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="$props.for">
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
        </Label>
        <slot />
        <InputError :message="error" />
    </div>
</template>
