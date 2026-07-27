<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import customers from '@/routes/customers';
import type { Customer } from '@/types';

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{ select: [customer: Customer] }>();

const search = ref('');
const results = ref<Customer[]>([]);
let debounceHandle: ReturnType<typeof setTimeout> | null = null;

async function runSearch() {
    const response = await fetch(
        customers.search.url({ query: { search: search.value } }),
        {
            headers: { Accept: 'application/json' },
        },
    );
    const payload = await response.json();
    results.value = payload.data ?? [];
}

watch(open, (value) => {
    if (value) {
        search.value = '';
        runSearch();
    }
});

watch(search, () => {
    if (debounceHandle) {
        clearTimeout(debounceHandle);
    }

    debounceHandle = setTimeout(runSearch, 250);
});

function choose(customer: Customer) {
    emit('select', customer);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Seleccionar cliente</DialogTitle>
            </DialogHeader>

            <Input
                v-model="search"
                placeholder="Buscar por nombre, teléfono o RFC..."
                autofocus
            />

            <ul class="max-h-80 divide-y overflow-y-auto rounded-md border">
                <li v-for="customer in results" :key="customer.id">
                    <button
                        type="button"
                        class="flex w-full flex-col items-start px-3 py-2 text-left hover:bg-accent"
                        @click="choose(customer)"
                    >
                        <span class="text-sm font-medium">{{
                            customer.name
                        }}</span>
                        <span class="text-xs text-muted-foreground">{{
                            customer.phone ??
                            customer.email ??
                            customer.customer_type_label
                        }}</span>
                    </button>
                </li>
                <li
                    v-if="!results.length"
                    class="p-4 text-center text-sm text-muted-foreground"
                >
                    Sin resultados.
                </li>
            </ul>

            <Button as-child variant="outline">
                <a :href="customers.create.url()" target="_blank" rel="noopener"
                    >Crear nuevo cliente</a
                >
            </Button>
        </DialogContent>
    </Dialog>
</template>
