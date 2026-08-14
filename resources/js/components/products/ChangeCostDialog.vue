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
    currentCost: string;
}>();

const open = ref(false);

const form = useForm({
    product_variant_id: props.variantId ?? null,
    cost: props.currentCost,
    reason: '',
});

function submit() {
    form.patch(ProductPriceController.updateCost.url(props.productId), {
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
                >Cambiar costo</Button
            >
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cambiar costo</DialogTitle>
                <DialogDescription>
                    El cambio queda registrado en el historial con el motivo
                    indicado.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <FormCurrencyInput
                    id="cost"
                    v-model="form.cost"
                    label="Nuevo costo"
                    required
                    tooltip="Nuevo costo unitario de este producto. Reemplaza al actual y queda registrado con fecha, usuario y motivo."
                    :error="form.errors.cost"
                />
                <FormField
                    label="Motivo del cambio"
                    for="reason"
                    required
                    :error="form.errors.reason"
                    tooltip="Explicación breve de por qué cambia el costo, visible en el historial de precios."
                >
                    <Input
                        id="reason"
                        v-model="form.reason"
                        required
                        placeholder="Aumento del proveedor, etc."
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
