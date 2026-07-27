<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CompanyController from '@/actions/App/Http/Controllers/Settings/CompanyController';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/settings/company';
import type { Company } from '@/types';

const props = defineProps<{
    company: Company;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mi empresa', href: edit() }],
    },
});

const form = useForm({
    name: props.company.name,
    legal_name: props.company.legal_name ?? '',
    tax_id: props.company.tax_id ?? '',
    address: props.company.address ?? '',
    phone: props.company.phone ?? '',
    email: props.company.email ?? '',
    currency: props.company.currency,
    timezone: props.company.timezone,
    primary_color: props.company.primary_color ?? '',
    secondary_color: props.company.secondary_color ?? '',
    status: props.company.status,
});

function submit() {
    form.patch(CompanyController.update.url());
}
</script>

<template>
    <Head title="Mi empresa" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Mi empresa"
            description="Datos generales, fiscales y de configuración visual."
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    label="Nombre comercial"
                    for="name"
                    required
                    :error="form.errors.name"
                >
                    <Input id="name" v-model="form.name" required />
                </FormField>
                <FormField
                    label="Razón social"
                    for="legal_name"
                    :error="form.errors.legal_name"
                >
                    <Input id="legal_name" v-model="form.legal_name" />
                </FormField>
                <FormField label="RFC" for="tax_id" :error="form.errors.tax_id">
                    <Input id="tax_id" v-model="form.tax_id" />
                </FormField>
                <FormField
                    label="Correo"
                    for="email"
                    :error="form.errors.email"
                >
                    <Input id="email" v-model="form.email" type="email" />
                </FormField>
                <FormField
                    label="Teléfono"
                    for="phone"
                    :error="form.errors.phone"
                >
                    <Input id="phone" v-model="form.phone" />
                </FormField>
                <FormField
                    label="Dirección"
                    for="address"
                    :error="form.errors.address"
                >
                    <Input id="address" v-model="form.address" />
                </FormField>
                <FormField
                    label="Moneda (ISO 4217)"
                    for="currency"
                    required
                    :error="form.errors.currency"
                >
                    <Input
                        id="currency"
                        v-model="form.currency"
                        maxlength="3"
                        class="uppercase"
                    />
                </FormField>
                <FormField
                    label="Zona horaria"
                    for="timezone"
                    required
                    :error="form.errors.timezone"
                >
                    <Input id="timezone" v-model="form.timezone" />
                </FormField>
                <FormField
                    label="Color primario"
                    for="primary_color"
                    :error="form.errors.primary_color"
                >
                    <Input
                        id="primary_color"
                        v-model="form.primary_color"
                        placeholder="#0f172a"
                    />
                </FormField>
                <FormField
                    label="Color secundario"
                    for="secondary_color"
                    :error="form.errors.secondary_color"
                >
                    <Input
                        id="secondary_color"
                        v-model="form.secondary_color"
                        placeholder="#64748b"
                    />
                </FormField>
            </div>

            <Button type="submit" :disabled="form.processing"
                >Guardar cambios</Button
            >
        </form>
    </div>
</template>
