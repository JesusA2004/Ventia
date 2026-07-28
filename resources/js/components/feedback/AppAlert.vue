<script setup lang="ts">
import {
    CircleCheckIcon,
    InfoIcon,
    OctagonXIcon,
    TriangleAlertIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { cn } from '@/lib/utils';

/**
 * Persistent, non-dismissable feedback for something the user should notice
 * before acting (as opposed to a toast, which is for transient confirmation
 * after an action already happened).
 */
const props = withDefaults(
    defineProps<{
        variant?: 'info' | 'success' | 'warning' | 'destructive';
        title?: string;
    }>(),
    { variant: 'info', title: undefined },
);

const icon = computed(
    () =>
        ({
            info: InfoIcon,
            success: CircleCheckIcon,
            warning: TriangleAlertIcon,
            destructive: OctagonXIcon,
        })[props.variant],
);

const colorClass = computed(
    () =>
        ({
            info: 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100 [&>svg]:text-sky-600 dark:[&>svg]:text-sky-400',
            success:
                'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100 [&>svg]:text-emerald-600 dark:[&>svg]:text-emerald-400',
            warning:
                'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100 [&>svg]:text-amber-600 dark:[&>svg]:text-amber-400',
            destructive: '',
        })[props.variant],
);
</script>

<template>
    <Alert
        :variant="variant === 'destructive' ? 'destructive' : 'default'"
        :class="cn(colorClass)"
    >
        <component :is="icon" />
        <AlertTitle v-if="title">{{ title }}</AlertTitle>
        <AlertDescription>
            <slot />
        </AlertDescription>
    </Alert>
</template>
