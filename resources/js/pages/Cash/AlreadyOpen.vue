<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { WalletIcon } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDateTime } from '@/lib/format';
import cash from '@/routes/cash';
import pos from '@/routes/pos';
import type { CashSession } from '@/types';

const props = defineProps<{
    session: CashSession;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Abrir caja', href: cash.sessions.create() }],
    },
});
</script>

<template>
    <Head title="Caja actualmente abierta" />

    <div class="mx-auto flex max-w-lg flex-col items-center gap-6 py-10">
        <WalletIcon class="size-10 text-muted-foreground" />
        <PageHeader
            title="Caja actualmente abierta"
            description="Ya tienes una sesión de caja abierta. Continúa al punto de venta para seguir vendiendo."
            class="text-center"
        />

        <div class="w-full space-y-4 rounded-xl border p-6">
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-muted-foreground">Caja</dt>
                <dd class="text-right font-medium">
                    {{ props.session.register_name }}
                </dd>

                <template v-if="props.session.branch_name">
                    <dt class="text-muted-foreground">Sucursal</dt>
                    <dd class="text-right font-medium">
                        {{ props.session.branch_name }}
                    </dd>
                </template>

                <dt class="text-muted-foreground">Fondo inicial</dt>
                <dd class="text-right font-medium">
                    {{ formatCurrency(props.session.opening_amount) }}
                </dd>

                <dt class="text-muted-foreground">Abierta desde</dt>
                <dd class="text-right font-medium">
                    {{ formatDateTime(props.session.opened_at) }}
                </dd>
            </dl>

            <div class="flex flex-col gap-2 pt-2">
                <Button as-child size="lg">
                    <Link :href="pos.index().url">
                        Continuar al punto de venta
                    </Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="cash.sessions.show(props.session.id).url">
                        Ver sesión
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
