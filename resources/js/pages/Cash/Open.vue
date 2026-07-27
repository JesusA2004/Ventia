<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LockKeyholeIcon } from '@lucide/vue';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import cash from '@/routes/cash';

defineProps<{
    registerOptions: {
        id: number;
        name: string;
        branch_id: number;
        branch_name: string;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Abrir caja', href: cash.sessions.create() }],
    },
});

const form = useForm({
    register_id: null as number | null,
    opening_amount: '0',
    opening_notes: '',
});

function submit() {
    form.post(cash.sessions.store.url());
}
</script>

<template>
    <Head title="Abrir caja" />

    <div class="mx-auto flex max-w-lg flex-col items-center gap-6 py-10">
        <LockKeyholeIcon class="size-10 text-muted-foreground" />
        <PageHeader
            title="Abrir caja"
            description="Selecciona tu caja y captura el fondo inicial para comenzar a vender."
            class="text-center"
        />

        <form
            class="w-full space-y-4 rounded-xl border p-6"
            @submit.prevent="submit"
        >
            <FormField
                label="Caja"
                for="register_id"
                required
                :error="form.errors.register_id"
            >
                <Select v-model="form.register_id">
                    <SelectTrigger id="register_id" class="w-full">
                        <SelectValue placeholder="Selecciona una caja" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in registerOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }} — {{ option.branch_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>

            <FormField
                label="Fondo inicial"
                for="opening_amount"
                required
                :error="form.errors.opening_amount"
            >
                <Input
                    id="opening_amount"
                    v-model="form.opening_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                />
            </FormField>

            <FormField
                label="Notas de apertura"
                for="opening_notes"
                :error="form.errors.opening_notes"
            >
                <Textarea
                    id="opening_notes"
                    v-model="form.opening_notes"
                    rows="2"
                />
            </FormField>

            <Button
                type="submit"
                class="w-full"
                size="lg"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Abriendo caja...' : 'Abrir caja' }}
            </Button>
        </form>
    </div>
</template>
