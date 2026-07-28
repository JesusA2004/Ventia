<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon, Loader2Icon, XIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export type SearchableSelectOption = {
    value: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        options: SearchableSelectOption[];
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        /** Shows a leading option that clears the selection, e.g. "Todos". */
        allowClear?: boolean;
        allLabel?: string;
        loading?: boolean;
        disabled?: boolean;
        error?: string;
        class?: string;
    }>(),
    {
        placeholder: 'Selecciona una opción',
        searchPlaceholder: 'Buscar...',
        emptyText: 'Sin resultados.',
        allowClear: true,
        allLabel: 'Todos',
        loading: false,
        disabled: false,
        error: undefined,
        class: undefined,
    },
);

const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();

const open = ref(false);

const selectedLabel = computed(
    () => props.options.find((o) => o.value === props.modelValue)?.label,
);

function select(value: string | null) {
    emit('update:modelValue', value);
    open.value = false;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <button
                type="button"
                role="combobox"
                :aria-expanded="open"
                :disabled="disabled || loading"
                :class="
                    cn(
                        'flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-background px-3 py-2 text-sm shadow-xs transition-colors hover:bg-accent/50 disabled:cursor-not-allowed disabled:opacity-50',
                        error && 'border-destructive',
                        props.class,
                    )
                "
            >
                <span
                    class="truncate"
                    :class="!selectedLabel && 'text-muted-foreground'"
                >
                    {{ selectedLabel ?? placeholder }}
                </span>
                <Loader2Icon
                    v-if="loading"
                    class="size-4 shrink-0 animate-spin text-muted-foreground"
                />
                <ChevronsUpDownIcon
                    v-else
                    class="size-4 shrink-0 text-muted-foreground"
                />
            </button>
        </PopoverTrigger>
        <PopoverContent
            class="w-(--reka-popover-trigger-width) p-0"
            align="start"
        >
            <Command>
                <div class="flex items-center border-b">
                    <CommandInput
                        :placeholder="searchPlaceholder"
                        class="flex-1"
                    />
                    <button
                        v-if="modelValue && allowClear"
                        type="button"
                        class="mr-2 rounded-sm p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        title="Limpiar selección"
                        @click="select(null)"
                    >
                        <XIcon class="size-3.5" />
                    </button>
                </div>
                <CommandList>
                    <CommandEmpty>{{ emptyText }}</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-if="allowClear"
                            value="__all__"
                            @select="select(null)"
                        >
                            <CheckIcon
                                :class="
                                    cn(
                                        'mr-2 size-4',
                                        modelValue === null
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )
                                "
                            />
                            {{ allLabel }}
                        </CommandItem>
                        <CommandItem
                            v-for="option in options"
                            :key="option.value"
                            :value="option.label"
                            @select="select(option.value)"
                        >
                            <CheckIcon
                                :class="
                                    cn(
                                        'mr-2 size-4',
                                        modelValue === option.value
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )
                                "
                            />
                            <span class="truncate">{{ option.label }}</span>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
    <p v-if="error" class="mt-1 text-sm text-destructive">{{ error }}</p>
</template>
