<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { PaginationLink, PaginationMeta } from '@/types/pagination';

/**
 * Renders "Mostrando X–Y de Z" + Anterior/Siguiente + page numbers on
 * desktop, and a compact Anterior / "Página N de M" / Siguiente row on
 * mobile. Deliberately ignores Laravel's own link.label text for the
 * prev/next controls (those come through as untranslated "pagination.previous"
 * / "pagination.next" keys since no Spanish pagination lang file exists) —
 * only the numbered page links reuse link.label, since those are just digits.
 */
const props = defineProps<{
    meta: PaginationMeta;
    links: PaginationLink[];
}>();

const previousLink = computed(() => props.links[0] ?? null);
const nextLink = computed(() => props.links[props.links.length - 1] ?? null);
const pageLinks = computed(() => props.links.slice(1, -1));
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-muted-foreground">
            Mostrando {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} de
            {{ meta.total }}
        </p>

        <!-- Mobile: compact Anterior / Página N de M / Siguiente -->
        <div class="flex items-center gap-2 sm:hidden">
            <Button
                as-child
                variant="outline"
                size="sm"
                :disabled="!previousLink?.url"
                :class="
                    cn(!previousLink?.url && 'pointer-events-none opacity-50')
                "
            >
                <Link
                    v-if="previousLink?.url"
                    :href="previousLink.url"
                    preserve-scroll
                    preserve-state
                >
                    Anterior
                </Link>
                <span v-else>Anterior</span>
            </Button>
            <p class="text-sm text-muted-foreground">
                Página {{ meta.current_page }} de {{ meta.last_page }}
            </p>
            <Button
                as-child
                variant="outline"
                size="sm"
                :disabled="!nextLink?.url"
                :class="cn(!nextLink?.url && 'pointer-events-none opacity-50')"
            >
                <Link
                    v-if="nextLink?.url"
                    :href="nextLink.url"
                    preserve-scroll
                    preserve-state
                >
                    Siguiente
                </Link>
                <span v-else>Siguiente</span>
            </Button>
        </div>

        <!-- Desktop: Anterior + page numbers + Siguiente -->
        <div class="hidden flex-wrap items-center gap-1 sm:flex">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        as-child
                        variant="outline"
                        size="icon"
                        aria-label="Página anterior"
                        :disabled="!previousLink?.url"
                        :class="
                            cn(
                                !previousLink?.url &&
                                    'pointer-events-none opacity-50',
                            )
                        "
                    >
                        <Link
                            v-if="previousLink?.url"
                            :href="previousLink.url"
                            preserve-scroll
                            preserve-state
                        >
                            <ChevronLeftIcon />
                        </Link>
                        <span v-else><ChevronLeftIcon /></span>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Página anterior</TooltipContent>
            </Tooltip>

            <template v-for="(link, index) in pageLinks" :key="index">
                <span
                    v-if="link.url === null"
                    class="px-2 text-sm text-muted-foreground"
                >
                    {{ link.label }}
                </span>
                <Button
                    v-else
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                >
                    <Link :href="link.url" preserve-scroll preserve-state>
                        {{ link.label }}
                    </Link>
                </Button>
            </template>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        as-child
                        variant="outline"
                        size="icon"
                        aria-label="Página siguiente"
                        :disabled="!nextLink?.url"
                        :class="
                            cn(
                                !nextLink?.url &&
                                    'pointer-events-none opacity-50',
                            )
                        "
                    >
                        <Link
                            v-if="nextLink?.url"
                            :href="nextLink.url"
                            preserve-scroll
                            preserve-state
                        >
                            <ChevronRightIcon />
                        </Link>
                        <span v-else><ChevronRightIcon /></span>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Página siguiente</TooltipContent>
            </Tooltip>
        </div>
    </div>
</template>
