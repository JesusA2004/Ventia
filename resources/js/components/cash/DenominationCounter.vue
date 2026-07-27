<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Input } from '@/components/ui/input';

const DENOMINATIONS = [1000, 500, 200, 100, 50, 20, 10, 5, 2, 1];

const counts = reactive<Record<number, number>>(
    Object.fromEntries(DENOMINATIONS.map((d) => [d, 0])),
);

const total = computed(() =>
    DENOMINATIONS.reduce((sum, d) => sum + d * (counts[d] || 0), 0),
);

defineExpose({ total, counts });
</script>

<template>
    <div class="space-y-2 rounded-lg border p-3">
        <div
            v-for="denomination in DENOMINATIONS"
            :key="denomination"
            class="flex items-center justify-between gap-2"
        >
            <span class="text-sm text-muted-foreground"
                >${{ denomination }}</span
            >
            <Input
                v-model.number="counts[denomination]"
                type="number"
                min="0"
                class="h-8 w-24 text-right"
            />
            <span class="w-24 text-right text-sm"
                >${{
                    (denomination * (counts[denomination] || 0)).toFixed(2)
                }}</span
            >
        </div>
        <div class="flex justify-between border-t pt-2 text-sm font-semibold">
            <span>Total contado</span>
            <span>${{ total.toFixed(2) }}</span>
        </div>
    </div>
</template>
