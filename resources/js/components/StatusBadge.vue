<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { Status } from '@/types';

const props = defineProps<{
    status: Status;
    label?: string;
}>();

const variant = computed(() =>
    props.status === 'active' ? 'default' : 'secondary',
);
</script>

<template>
    <Badge :variant="variant">{{
        label ?? (status === 'active' ? 'Activo' : 'Inactivo')
    }}</Badge>
</template>
