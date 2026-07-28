<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDateTime } from '@/lib/format';
import type { Sale } from '@/types';

type CompanyInfo = {
    name: string;
    legal_name: string | null;
    tax_id: string | null;
    address: string | null;
    phone: string | null;
};
type BranchInfo = { name: string; address: string | null };

defineProps<{
    sale: Sale;
    company: CompanyInfo;
    branch: BranchInfo;
}>();

const width = ref<'58mm' | '80mm' | 'normal'>('80mm');

function printTicket() {
    window.print();
}
</script>

<template>
    <Head :title="`Ticket ${sale.folio}`" />

    <div class="mx-auto max-w-2xl p-6 print:p-0">
        <div class="mb-4 flex justify-center gap-2 print:hidden">
            <Button
                size="sm"
                :variant="width === '58mm' ? 'default' : 'outline'"
                @click="width = '58mm'"
                >58 mm</Button
            >
            <Button
                size="sm"
                :variant="width === '80mm' ? 'default' : 'outline'"
                @click="width = '80mm'"
                >80 mm</Button
            >
            <Button
                size="sm"
                :variant="width === 'normal' ? 'default' : 'outline'"
                @click="width = 'normal'"
                >Normal</Button
            >
            <Button size="sm" @click="printTicket">Imprimir</Button>
        </div>

        <div
            class="mx-auto space-y-2 border p-4 font-mono text-xs print:border-0"
            :class="{
                'max-w-[58mm]': width === '58mm',
                'max-w-[80mm]': width === '80mm',
                'max-w-md': width === 'normal',
            }"
        >
            <div class="text-center">
                <p class="text-sm font-bold">{{ company.name }}</p>
                <p v-if="company.legal_name">{{ company.legal_name }}</p>
                <p v-if="company.tax_id">RFC: {{ company.tax_id }}</p>
                <p v-if="branch.address ?? company.address">
                    {{ branch.address ?? company.address }}
                </p>
                <p v-if="company.phone">Tel: {{ company.phone }}</p>
            </div>

            <div class="border-t border-dashed pt-2">
                <p>Sucursal: {{ branch.name }}</p>
                <p>Folio: {{ sale.folio }}</p>
                <p>
                    Fecha:
                    {{
                        sale.completed_at
                            ? formatDateTime(sale.completed_at)
                            : ''
                    }}
                </p>
                <p>Cajero: {{ sale.cashier_name }}</p>
                <p>Cliente: {{ sale.customer_name }}</p>
            </div>

            <div class="border-t border-dashed pt-2">
                <div v-for="item in sale.items" :key="item.id" class="mb-1">
                    <div class="flex justify-between">
                        <span>{{ item.product_name }}</span>
                        <span>{{ formatCurrency(item.total) }}</span>
                    </div>
                    <div
                        class="flex justify-between text-[10px] text-muted-foreground"
                    >
                        <span
                            >{{ item.quantity }} x
                            {{ formatCurrency(item.unit_price) }}</span
                        >
                        <span v-if="Number(item.discount_amount) > 0"
                            >Desc:
                            {{ formatCurrency(item.discount_amount) }}</span
                        >
                    </div>
                </div>
            </div>

            <div class="space-y-0.5 border-t border-dashed pt-2">
                <div class="flex justify-between">
                    <span>Subtotal</span
                    ><span>{{ formatCurrency(sale.subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Descuento</span
                    ><span>-{{ formatCurrency(sale.discount_total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Impuestos</span
                    ><span>{{ formatCurrency(sale.tax_total) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold">
                    <span>TOTAL</span
                    ><span>{{ formatCurrency(sale.total) }}</span>
                </div>
            </div>

            <div class="space-y-0.5 border-t border-dashed pt-2">
                <div
                    v-for="payment in sale.payments"
                    :key="payment.id"
                    class="flex justify-between"
                >
                    <span>{{ payment.payment_method_name }}</span>
                    <span>{{ formatCurrency(payment.amount) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Recibido</span
                    ><span>{{ formatCurrency(sale.amount_received) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Cambio</span
                    ><span>{{ formatCurrency(sale.change_amount) }}</span>
                </div>
            </div>

            <div class="border-t border-dashed pt-2 text-center">
                <p>¡Gracias por su compra!</p>
                <p class="text-[10px]">
                    Este ticket es su comprobante de compra.
                </p>
            </div>
        </div>
    </div>
</template>
