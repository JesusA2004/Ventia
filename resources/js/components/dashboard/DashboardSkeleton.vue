<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-3">
            <Skeleton class="h-7 w-40" />
            <Skeleton class="h-4 w-72" />
            <div class="flex flex-wrap gap-2">
                <Skeleton class="h-9 w-56" />
                <Skeleton class="h-9 w-44" />
                <Skeleton class="h-9 w-44" />
                <Skeleton class="h-9 w-44" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <Skeleton v-for="i in 4" :key="i" class="h-24 rounded-xl" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Skeleton v-for="i in 4" :key="i" class="h-64 rounded-xl" />
        </div>
    </div>
</template>
