<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { PanelLeftClose, PanelLeftOpen } from "@lucide/vue"
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, toggleSidebar } = useSidebar()

const label = computed(() =>
  isMobile.value || state.value === 'collapsed' ? 'Expandir menú' : 'Contraer menú',
)
</script>

<template>
  <Tooltip>
    <TooltipTrigger as-child>
      <Button
        data-sidebar="trigger"
        data-slot="sidebar-trigger"
        variant="ghost"
        size="icon"
        :aria-label="label"
        :class="cn('h-7 w-7', props.class)"
        @click="toggleSidebar"
      >
        <PanelLeftOpen v-if="isMobile || state === 'collapsed'" />
        <PanelLeftClose v-else />
        <span class="sr-only">{{ label }}</span>
      </Button>
    </TooltipTrigger>
    <TooltipContent>{{ label }}</TooltipContent>
  </Tooltip>
</template>
