<script setup lang="ts">
import { CircleHelpIcon } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type Props = {
    for?: string;
    label: string;
    error?: string;
    required?: boolean;
    /** Short explanation shown in a tooltip next to the label, for fields whose purpose isn't obvious from the name alone. */
    tooltip?: string;
};

defineProps<Props>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="$props.for" class="inline-flex items-center gap-1.5">
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
            <Tooltip v-if="tooltip">
                <TooltipTrigger
                    type="button"
                    class="text-muted-foreground hover:text-foreground"
                    :aria-label="`Ayuda: ${label}`"
                >
                    <CircleHelpIcon class="size-3.5" />
                </TooltipTrigger>
                <TooltipContent>{{ tooltip }}</TooltipContent>
            </Tooltip>
        </Label>
        <slot />
        <InputError :message="error" />
    </div>
</template>
