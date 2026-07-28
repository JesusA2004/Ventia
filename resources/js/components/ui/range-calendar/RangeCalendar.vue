<script setup lang="ts">
import type { DateRange } from 'reka-ui';
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';
import {
    RangeCalendarCell,
    RangeCalendarCellTrigger,
    RangeCalendarGrid,
    RangeCalendarGridBody,
    RangeCalendarGridHead,
    RangeCalendarGridRow,
    RangeCalendarHeadCell,
    RangeCalendarHeader,
    RangeCalendarHeading,
    RangeCalendarNext,
    RangeCalendarPrev,
    RangeCalendarRoot,
} from 'reka-ui';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        modelValue?: DateRange;
        placeholder?: DateRange['start'];
        numberOfMonths?: number;
        weekStartsOn?: 0 | 1;
    }>(),
    {
        numberOfMonths: 1,
        weekStartsOn: 1,
    },
);

defineEmits<{
    'update:modelValue': [DateRange];
    'update:placeholder': [DateRange['start']];
}>();
</script>

<template>
    <RangeCalendarRoot
        v-slot="{ grid, weekDays }"
        :model-value="modelValue"
        :placeholder="placeholder"
        :number-of-months="numberOfMonths"
        :week-starts-on="weekStartsOn"
        :paged-navigation="numberOfMonths > 1"
        locale="es"
        weekday-format="short"
        fixed-weeks
        class="p-3"
        @update:model-value="$emit('update:modelValue', $event)"
        @update:placeholder="$emit('update:placeholder', $event)"
    >
        <RangeCalendarHeader class="relative flex w-full items-center justify-between">
            <RangeCalendarPrev
                class="inline-flex size-7 items-center justify-center rounded-md border bg-transparent hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            >
                <ChevronLeftIcon class="size-4" />
            </RangeCalendarPrev>
            <RangeCalendarHeading class="text-sm font-medium capitalize" />
            <RangeCalendarNext
                class="inline-flex size-7 items-center justify-center rounded-md border bg-transparent hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
            >
                <ChevronRightIcon class="size-4" />
            </RangeCalendarNext>
        </RangeCalendarHeader>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row">
            <RangeCalendarGrid
                v-for="month in grid"
                :key="month.value.toString()"
                class="w-full border-collapse select-none space-y-1"
            >
                <RangeCalendarGridHead>
                    <RangeCalendarGridRow class="flex w-full justify-between">
                        <RangeCalendarHeadCell
                            v-for="day in weekDays"
                            :key="day"
                            class="w-9 rounded-md text-[0.8rem] font-normal text-muted-foreground capitalize"
                        >
                            {{ day }}
                        </RangeCalendarHeadCell>
                    </RangeCalendarGridRow>
                </RangeCalendarGridHead>
                <RangeCalendarGridBody>
                    <RangeCalendarGridRow
                        v-for="(weekDates, index) in month.rows"
                        :key="`weekDate-${index}`"
                        class="flex w-full justify-between"
                    >
                        <RangeCalendarCell
                            v-for="weekDate in weekDates"
                            :key="weekDate.toString()"
                            :date="weekDate"
                            class="relative size-9 p-0 text-center text-sm focus-within:relative focus-within:z-20 [&:has([data-selected])]:bg-accent [&:has([data-selection-end])]:rounded-r-md [&:has([data-selection-start])]:rounded-l-md"
                        >
                            <RangeCalendarCellTrigger
                                :day="weekDate"
                                :month="month.value"
                                :class="
                                    cn(
                                        'flex size-9 items-center justify-center rounded-md p-0 text-sm font-normal transition-colors hover:bg-accent hover:text-accent-foreground',
                                        'data-[today]:font-semibold',
                                        'data-[outside-view]:pointer-events-none data-[outside-view]:text-muted-foreground data-[outside-view]:opacity-40',
                                        'data-[disabled]:pointer-events-none data-[disabled]:opacity-40',
                                        'data-[unavailable]:pointer-events-none data-[unavailable]:text-muted-foreground data-[unavailable]:line-through',
                                        'data-[selected]:bg-accent data-[selected]:text-accent-foreground',
                                        'data-[selection-start]:bg-primary data-[selection-start]:text-primary-foreground data-[selection-start]:hover:bg-primary data-[selection-start]:hover:text-primary-foreground',
                                        'data-[selection-end]:bg-primary data-[selection-end]:text-primary-foreground data-[selection-end]:hover:bg-primary data-[selection-end]:hover:text-primary-foreground',
                                    )
                                "
                            />
                        </RangeCalendarCell>
                    </RangeCalendarGridRow>
                </RangeCalendarGridBody>
            </RangeCalendarGrid>
        </div>
    </RangeCalendarRoot>
</template>
