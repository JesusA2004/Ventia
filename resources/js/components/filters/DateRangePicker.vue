<script setup lang="ts">
import {
    endOfMonth,
    getLocalTimeZone,
    parseDate,
    startOfMonth,
    startOfWeek,
    today,
} from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import type { DateRange } from 'reka-ui';
import { computed, ref, shallowRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

export type DateRangeValue = {
    preset: string;
    date_from: string;
    date_to: string;
};

const props = withDefaults(
    defineProps<{
        modelValue: { preset: string; date_from: string; date_to: string };
        class?: string;
    }>(),
    { class: undefined },
);

const emit = defineEmits<{ 'update:modelValue': [DateRangeValue] }>();

const open = ref(false);
const isDesktop = useMediaQuery('(min-width: 768px)');
const numberOfMonths = computed(() => (isDesktop.value ? 2 : 1));
const tz = getLocalTimeZone();

// shallowRef: CalendarDate/DateRange are immutable value objects (every
// operation returns a new instance) — a deep ref would make Vue's
// UnwrapRef transform recurse into the class internals, which both breaks
// TS's structural check against reka-ui's DateRange union and is
// unnecessary reactivity overhead for values that are always replaced
// wholesale, never mutated in place.
const draft = shallowRef<DateRange>({
    start: parseDate(props.modelValue.date_from),
    end: parseDate(props.modelValue.date_to),
});

watch(
    () => [props.modelValue.date_from, props.modelValue.date_to] as const,
    ([from, to]) => {
        draft.value = { start: parseDate(from), end: parseDate(to) };
    },
);

const triggerLabel = computed(() => {
    if (props.modelValue.date_from === props.modelValue.date_to) {
        return formatDate(props.modelValue.date_from);
    }

    return `${formatDate(props.modelValue.date_from)} – ${formatDate(props.modelValue.date_to)}`;
});

type Shortcut = { label: string; preset: string; range: () => DateRange };

const now = today(tz);

const shortcuts: Shortcut[] = [
    { label: 'Hoy', preset: 'today', range: () => ({ start: now, end: now }) },
    {
        label: 'Ayer',
        preset: 'yesterday',
        range: () => {
            const d = now.subtract({ days: 1 });

            return { start: d, end: d };
        },
    },
    {
        label: 'Últimos 7 días',
        preset: 'custom',
        range: () => ({ start: now.subtract({ days: 6 }), end: now }),
    },
    {
        label: 'Últimos 30 días',
        preset: 'custom',
        range: () => ({ start: now.subtract({ days: 29 }), end: now }),
    },
    {
        label: 'Esta semana',
        preset: 'this_week',
        range: () => ({ start: startOfWeek(now, 'es'), end: now }),
    },
    {
        label: 'Semana anterior',
        preset: 'last_week',
        range: () => {
            const start = startOfWeek(now, 'es').subtract({ weeks: 1 });

            return { start, end: start.add({ days: 6 }) };
        },
    },
    {
        label: 'Este mes',
        preset: 'this_month',
        range: () => ({ start: startOfMonth(now), end: now }),
    },
    {
        label: 'Mes anterior',
        preset: 'last_month',
        range: () => {
            const start = startOfMonth(now).subtract({ months: 1 });

            return { start, end: endOfMonth(start) };
        },
    },
];

function applyShortcut(shortcut: Shortcut) {
    const range = shortcut.range();
    draft.value = range;
    commit(shortcut.preset, range);
}

function commit(preset: string, range: DateRange) {
    if (!range.start || !range.end) {
        return;
    }

    open.value = false;
    emit('update:modelValue', {
        preset,
        date_from: range.start.toString(),
        date_to: range.end.toString(),
    });
}

function applyDraft() {
    commit('custom', draft.value);
}

function clearDraft() {
    draft.value = { start: now, end: now };
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="cn('justify-start gap-2 font-normal', props.class)"
            >
                <CalendarIcon class="size-4 text-muted-foreground" />
                {{ triggerLabel }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <div class="flex flex-col sm:flex-row">
                <div
                    class="flex shrink-0 flex-row gap-1 overflow-x-auto border-b p-2 sm:w-40 sm:flex-col sm:overflow-visible sm:border-r sm:border-b-0"
                >
                    <button
                        v-for="shortcut in shortcuts"
                        :key="shortcut.label"
                        type="button"
                        class="shrink-0 rounded-md px-2 py-1.5 text-left text-sm whitespace-nowrap hover:bg-accent hover:text-accent-foreground"
                        @click="applyShortcut(shortcut)"
                    >
                        {{ shortcut.label }}
                    </button>
                </div>
                <div class="flex flex-col">
                    <RangeCalendar
                        v-model="draft"
                        :number-of-months="numberOfMonths"
                        :placeholder="draft.start"
                    />
                    <div
                        class="flex items-center justify-between gap-2 border-t p-3"
                    >
                        <span class="text-xs text-muted-foreground">
                            {{
                                draft.start
                                    ? formatDate(draft.start.toString())
                                    : '—'
                            }}
                            –
                            {{
                                draft.end
                                    ? formatDate(draft.end.toString())
                                    : '—'
                            }}
                        </span>
                        <div class="flex gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="clearDraft"
                            >
                                Limpiar
                            </Button>
                            <Button size="sm" @click="applyDraft"
                                >Aplicar</Button
                            >
                        </div>
                    </div>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
