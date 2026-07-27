<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        Ventia mark: an abstract "V" built from two rising bars (the right
        arm taller, evoking an upward sales trend) meeting at a single point,
        with a small dot beneath it standing in for a scanned/completed sale.
        Monochrome by design (fill="currentColor") so callers control color
        via text-* utility classes against whatever badge/background they
        place it on.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <rect
            x="-3.5"
            y="-26"
            width="7"
            height="26"
            rx="3.5"
            fill="currentColor"
            transform="translate(20,32) rotate(-26)"
        />
        <rect
            x="-3.5"
            y="-30"
            width="7"
            height="30"
            rx="3.5"
            fill="currentColor"
            transform="translate(20,32) rotate(26)"
        />
        <circle cx="20" cy="35.5" r="2.75" fill="currentColor" />
    </svg>
</template>
