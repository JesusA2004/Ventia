<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProductPriceController from '@/actions/App/Http/Controllers/Catalog/ProductPriceController';
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
                <FormField
                    label="Nuevo costo"
                    for="cost"
                    required
                    :error="form.errors.cost"
                >
                    <Input
                        id="cost"
                        v-model="form.cost"
                        type="number"
                        step="0.0001"
                        min="0"
                        required
                    />
                </FormField>
                <FormField
                    label="Motivo del cambio"
                    for="reason"
                    required
                    :error="form.errors.reason"
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
