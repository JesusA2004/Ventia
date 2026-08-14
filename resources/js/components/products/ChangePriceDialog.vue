<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProductPriceController from '@/actions/App/Http/Controllers/Catalog/ProductPriceController';
import FormCurrencyInput from '@/components/forms/FormCurrencyInput.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    productId: number;
    variantId?: number | null;
    currentPrice: string;
}>();

const open = ref(false);

const form = useForm({
    product_variant_id: props.variantId ?? null,
    price: props.currentPrice,
    reason: '',
    branch_id: null as number | null,
    price_list_id: null as number | null,
});

function submit() {
    form.patch(ProductPriceController.updatePrice.url(props.productId), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset('reason');
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button type="button" variant="outline" size="sm"
                >Cambiar precio</Button
            >
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cambiar precio de venta</DialogTitle>
                <DialogDescription>
                    El cambio queda registrado en el historial de precios con el
                    motivo indicado.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <FormCurrencyInput
                    id="price"
                    v-model="form.price"
                    label="Nuevo precio"
                    required
                    tooltip="Nuevo precio de venta de este producto. Reemplaza al actual y queda registrado con fecha, usuario y motivo."
                    :error="form.errors.price"
                />
                <FormField
                    label="Motivo del cambio"
                    for="reason"
                    required
                    :error="form.errors.reason"
                    tooltip="Explicación breve de por qué cambia el precio, visible en el historial de precios."
                >
                    <Input
                        id="reason"
                        v-model="form.reason"
                        required
                        placeholder="Ajuste por inflación, promoción, etc."
                    />
                </FormField>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing"
                        >Guardar y registrar historial</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
