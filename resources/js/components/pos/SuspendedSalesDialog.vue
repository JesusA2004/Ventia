<script setup lang="ts">
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { firstErrorMessage } from '@/lib/apiErrors';
import sales from '@/routes/sales';
import type { Sale } from '@/types';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{ registerId: number | null }>();

const emit = defineEmits<{ resumed: [sale: Sale] }>();

const list = ref<Sale[]>([]);
const loading = ref(false);

async function load() {
    if (!props.registerId) {
        return;
    }

    loading.value = true;

    try {
        const response = await fetch(
            sales.suspended.url({ query: { register_id: props.registerId } }),
            {
                headers: { Accept: 'application/json' },
            },
        );
        const payload = await response.json();
        list.value = payload.data ?? [];
    } finally {
        loading.value = false;
    }
}

watch(open, (value) => {
    if (value) {
        load();
    }
});

async function resume(sale: Sale) {
    const response = await fetch(sales.resume.url(sale.id), {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN':
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? '',
        },
    });

    if (!response.ok) {
        const error = await response.json().catch(() => null);
        toast.error(firstErrorMessage(error, 'No se pudo recuperar la venta.'));

        return;
    }

    const payload = await response.json();
    emit('resumed', payload.data);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Ventas suspendidas</DialogTitle>
            </DialogHeader>

            <p v-if="loading" class="text-sm text-muted-foreground">
                Cargando...
            </p>
            <ul
                v-else
                class="max-h-96 divide-y overflow-y-auto rounded-md border"
            >
                <li
                    v-for="sale in list"
                    :key="sale.id"
                    class="flex items-center justify-between px-3 py-2"
                >
                    <div>
                        <p class="text-sm font-medium">{{ sale.folio }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ sale.customer_name }} ·
                            {{ sale.items?.length ?? 0 }} productos
                        </p>
                    </div>
                    <Button size="sm" @click="resume(sale)">Recuperar</Button>
                </li>
                <li
                    v-if="!list.length"
                    class="p-4 text-center text-sm text-muted-foreground"
                >
                    No hay ventas suspendidas.
                </li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
