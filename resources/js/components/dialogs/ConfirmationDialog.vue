<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type Props = {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'destructive';
    /** Shown on hover over the trigger — required for icon-only triggers. */
    tooltip?: string;
};

withDefaults(defineProps<Props>(), {
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    variant: 'destructive',
});

const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <AlertDialog>
        <Tooltip v-if="tooltip">
            <TooltipTrigger as-child>
                <AlertDialogTrigger as-child>
                    <slot name="trigger" />
                </AlertDialogTrigger>
            </TooltipTrigger>
            <TooltipContent>{{ tooltip }}</TooltipContent>
        </Tooltip>
        <AlertDialogTrigger v-else as-child>
            <slot name="trigger" />
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription v-if="description">
                    {{ description }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>{{ cancelLabel }}</AlertDialogCancel>
                <AlertDialogAction
                    :class="
                        variant === 'destructive'
                            ? 'bg-destructive text-white hover:bg-destructive/90'
                            : ''
                    "
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
