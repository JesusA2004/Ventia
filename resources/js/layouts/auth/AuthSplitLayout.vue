<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-2">
        <!-- Visual panel: hidden on mobile, shown from lg breakpoint up. -->
        <div
            class="relative hidden overflow-hidden bg-zinc-900 lg:flex lg:flex-col lg:justify-between lg:p-10 dark:bg-zinc-950"
        >
            <div class="absolute inset-0 -z-10">
                <div
                    class="absolute -top-24 -left-24 size-96 rounded-full bg-indigo-600/30 blur-3xl"
                />
                <div
                    class="absolute right-0 bottom-0 size-[28rem] rounded-full bg-violet-600/20 blur-3xl"
                />
            </div>

            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-2 text-white"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15"
                >
                    <AppLogoIcon class="size-5 text-white" />
                </span>
                <span class="text-lg font-semibold tracking-tight">Ventia</span>
            </Link>

            <div class="relative z-10 max-w-md">
                <h2 class="text-3xl leading-tight font-semibold text-white">
                    Tu negocio, ventas e inventario en un solo lugar.
                </h2>
                <p class="mt-3 text-sm text-zinc-400">
                    Punto de venta, caja, productos, inventario y reportes para
                    tu empresa, todo desde un mismo sistema.
                </p>
            </div>

            <!-- Abstract POS-themed panel: ticket card, mini bar chart, and
                 barcode strokes — built entirely from utility shapes, no
                 stock photography or external assets. -->
            <div class="relative z-10 mt-10 flex items-end gap-4">
                <div
                    class="flex h-40 w-28 flex-col justify-between rounded-xl border border-white/10 bg-white/5 p-3 shadow-xl backdrop-blur-sm"
                >
                    <div class="space-y-1.5">
                        <div class="h-1.5 w-full rounded-full bg-white/25" />
                        <div class="h-1.5 w-3/4 rounded-full bg-white/15" />
                        <div class="h-1.5 w-5/6 rounded-full bg-white/15" />
                    </div>
                    <div class="flex items-end gap-0.5">
                        <div
                            v-for="bar in 8"
                            :key="bar"
                            class="h-2.5 w-1 rounded-sm bg-white/40"
                        />
                    </div>
                    <div class="h-1.5 w-1/2 rounded-full bg-indigo-400/70" />
                </div>

                <div
                    class="flex h-28 w-24 flex-col justify-end gap-1.5 rounded-xl border border-white/10 bg-white/5 p-3 shadow-xl backdrop-blur-sm"
                >
                    <div class="flex items-end gap-1">
                        <div class="h-6 w-2.5 rounded-sm bg-indigo-400/60" />
                        <div class="h-10 w-2.5 rounded-sm bg-indigo-400/80" />
                        <div class="h-4 w-2.5 rounded-sm bg-indigo-400/40" />
                        <div class="h-8 w-2.5 rounded-sm bg-violet-400/70" />
                    </div>
                </div>

                <div
                    class="hidden h-16 w-20 flex-col justify-center gap-[3px] rounded-xl border border-white/10 bg-white/5 p-3 shadow-xl backdrop-blur-sm xl:flex"
                >
                    <div class="flex h-8 items-stretch gap-[2px]">
                        <div
                            v-for="stripe in 12"
                            :key="stripe"
                            class="bg-white/50"
                            :style="{
                                width: stripe % 3 === 0 ? '3px' : '1.5px',
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Form panel -->
        <div
            class="flex flex-col items-center justify-center gap-6 bg-background px-6 py-10 sm:px-10"
        >
            <Link :href="home()" class="flex items-center gap-2 lg:hidden">
                <span
                    class="flex size-9 items-center justify-center rounded-lg bg-indigo-600 text-white"
                >
                    <AppLogoIcon class="size-5" />
                </span>
                <span class="text-lg font-semibold tracking-tight">Ventia</span>
            </Link>

            <div class="flex w-full max-w-sm flex-col gap-6">
                <div
                    v-if="title || description"
                    class="flex flex-col gap-1 text-center sm:text-left"
                >
                    <h1
                        v-if="title"
                        class="text-xl font-semibold tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
