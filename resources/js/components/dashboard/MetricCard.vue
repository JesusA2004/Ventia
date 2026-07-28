<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { TrendingDownIcon, TrendingUpIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatPercentage } from '@/lib/format';

const props = defineProps<{
    icon: LucideIcon;
    label: string;
    value: string;
    /** Percent change vs. the previous equivalent period, if available. */
    comparison?: number | null;
    /** Small recent-history series for the inline sparkline (oldest first). */
    trend?: number[];
    tooltip?: string;
    loading?: boolean;
}>();

const comparisonTone = computed(() => {
    if (props.comparison === null || props.comparison === undefined) {
        return null;
    }

    return props.comparison >= 0 ? 'positive' : 'negative';
});

const sparklinePath = computed(() => {
    const points = props.trend;

    if (!points || points.length < 2) {
        return null;
    }

    const max = Math.max(...points, 0);
    const min = Math.min(...points, 0);
    const range = max - min || 1;
    const width = 100;
    const height = 28;
    const step = width / (points.length - 1);

    return points
        .map((value, index) => {
            const x = index * step;
            const y = height - ((value - min) / range) * height;

            return `${index === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
});
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <template v-if="loading">
            <Skeleton class="h-4 w-24" />
            <Skeleton class="mt-3 h-7 w-32" />
            <Skeleton class="mt-2 h-3 w-20" />
        </template>
        <template v-else>
            <div class="flex items-center justify-between gap-2">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <component :is="icon" class="size-4" />
                    {{ label }}
                    <Tooltip v-if="tooltip">
                        <TooltipTrigger as-child>
                            <span
                                class="flex size-4 items-center justify-center rounded-full border text-[10px] leading-none text-muted-foreground"
                                >?</span
                            >
                        </TooltipTrigger>
                        <TooltipContent>{{ tooltip }}</TooltipContent>
                    </Tooltip>
                </div>
                <svg
                    v-if="sparklinePath"
                    viewBox="0 0 100 28"
                    class="h-6 w-16 shrink-0 text-primary/70"
                    preserveAspectRatio="none"
                >
                    <path
                        :d="sparklinePath"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        vector-effect="non-scaling-stroke"
                    />
                </svg>
            </div>
            <p class="mt-1 text-2xl font-bold">{{ value }}</p>
            <p
                v-if="comparisonTone"
                class="mt-1 flex items-center gap-1 text-xs"
                :class="
                    comparisonTone === 'positive'
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-red-600 dark:text-red-400'
                "
            >
                <component
                    :is="
                        comparisonTone === 'positive'
                            ? TrendingUpIcon
                            : TrendingDownIcon
                    "
                    class="size-3"
                />
                {{ comparison! > 0 ? '+' : ''
                }}{{ formatPercentage(comparison!) }}
                <span class="text-muted-foreground">vs. período anterior</span>
            </p>
        </template>
    </div>
</template>
