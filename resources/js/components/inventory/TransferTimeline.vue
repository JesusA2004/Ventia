<script setup lang="ts">
import { CheckIcon, XIcon } from '@lucide/vue';
import { computed } from 'vue';
import { formatDateTime } from '@/lib/format';
import type { StockTransfer } from '@/types';

const props = defineProps<{ transfer: StockTransfer }>();

type Step = {
    key: string;
    label: string;
    at: string | null;
    by: string | null | undefined;
    done: boolean;
};

/**
 * The transfer row already carries a timestamp + actor per transition
 * (requested/approved/shipped/received) — no separate history table needed,
 * this just arranges what's already on the model into a visual sequence.
 */
const steps = computed<Step[]>(() => {
    const t = props.transfer;

    const base: Step[] = [
        {
            key: 'created',
            label: 'Creada',
            at: t.created_at,
            by: t.requested_by_name,
            done: true,
        },
        {
            key: 'requested',
            label: 'Enviada a aprobación',
            at: t.requested_at,
            by: t.requested_by_name,
            done: t.requested_at !== null,
        },
        {
            key: 'approved',
            label: 'Aprobada',
            at: t.approved_at,
            by: t.approved_by_name,
            done: t.approved_at !== null,
        },
        {
            key: 'shipped',
            label: 'En tránsito',
            at: t.shipped_at,
            by: t.shipped_by_name,
            done: t.shipped_at !== null,
        },
        {
            key: 'received',
            label:
                t.status === 'partially_received'
                    ? 'Recibida parcialmente'
                    : 'Recibida',
            at: t.received_at,
            by: t.received_by_name,
            done: t.received_at !== null,
        },
    ];

    return base;
});

const isCancelled = computed(() => props.transfer.status === 'cancelled');
</script>

<template>
    <div class="rounded-lg border p-4">
        <p v-if="isCancelled" class="mb-3 flex items-center gap-2 text-sm font-medium text-destructive">
            <XIcon class="size-4" />
            Transferencia cancelada
            <span
                v-if="transfer.cancelled_at"
                class="font-normal text-muted-foreground"
                >· {{ formatDateTime(transfer.cancelled_at) }}</span
            >
        </p>

        <!-- Mobile: vertical list -->
        <ol class="flex flex-col gap-4 sm:hidden">
            <li v-for="step in steps" :key="step.key" class="flex gap-3">
                <span
                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border text-[10px]"
                    :class="
                        step.done
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-muted-foreground/40 text-muted-foreground'
                    "
                >
                    <CheckIcon v-if="step.done" class="size-3" />
                </span>
                <div>
                    <p
                        class="text-sm font-medium"
                        :class="!step.done && 'text-muted-foreground'"
                    >
                        {{ step.label }}
                    </p>
                    <p v-if="step.done" class="text-xs text-muted-foreground">
                        {{ formatDateTime(step.at) }}
                        <span v-if="step.by"> · {{ step.by }}</span>
                    </p>
                </div>
            </li>
        </ol>

        <!-- Desktop: horizontal connected steps -->
        <ol class="hidden sm:flex sm:items-start">
            <li
                v-for="(step, index) in steps"
                :key="step.key"
                class="flex flex-1 flex-col items-center text-center"
            >
                <div class="flex w-full items-center">
                    <div
                        class="h-px flex-1"
                        :class="
                            index === 0
                                ? 'invisible'
                                : steps[index - 1].done
                                  ? 'bg-primary'
                                  : 'bg-muted-foreground/30'
                        "
                    />
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full border text-xs"
                        :class="
                            step.done
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-muted-foreground/40 text-muted-foreground'
                        "
                    >
                        <CheckIcon v-if="step.done" class="size-3.5" />
                    </span>
                    <div
                        class="h-px flex-1"
                        :class="
                            index === steps.length - 1
                                ? 'invisible'
                                : step.done
                                  ? 'bg-primary'
                                  : 'bg-muted-foreground/30'
                        "
                    />
                </div>
                <p
                    class="mt-2 text-xs font-medium"
                    :class="!step.done && 'text-muted-foreground'"
                >
                    {{ step.label }}
                </p>
                <p v-if="step.done" class="mt-0.5 text-[11px] text-muted-foreground">
                    {{ formatDateTime(step.at) }}
                </p>
                <p v-if="step.done && step.by" class="text-[11px] text-muted-foreground">
                    {{ step.by }}
                </p>
            </li>
        </ol>
    </div>
</template>
