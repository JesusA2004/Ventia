<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CompanyController from '@/actions/App/Http/Controllers/Settings/CompanyController';
import FormColorInput from '@/components/forms/FormColorInput.vue';
import FormField from '@/components/forms/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { isValidHex, readableForeground } from '@/lib/color';
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

const previewPrimary = computed(() =>
    isValidHex(form.primary_color) ? form.primary_color : '#0f172a',
);
const previewPrimaryForeground = computed(() =>
    readableForeground(previewPrimary.value),
);
const previewSecondary = computed(() =>
    isValidHex(form.secondary_color) ? form.secondary_color : '#64748b',
);
const previewSecondaryForeground = computed(() =>
    readableForeground(previewSecondary.value),
);
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
                <FormField
                    label="RFC"
                    for="tax_id"
                    :error="form.errors.tax_id"
                    tooltip="Registro Federal de Contribuyentes de la empresa, usado en facturación y documentos fiscales."
                >
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
                    tooltip="Código de 3 letras de la moneda en la que se registran precios y ventas (por ejemplo, MXN, USD)."
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
                    tooltip="Zona horaria usada para fechas y horarios de la empresa (por ejemplo, America/Mexico_City)."
                >
                    <Input id="timezone" v-model="form.timezone" />
                </FormField>
            </div>

            <div class="space-y-4 rounded-lg border p-4">
                <div>
                    <p class="text-sm font-medium">Identidad visual</p>
                    <p class="text-xs text-muted-foreground">
                        Elige los colores de esta empresa gráficamente o
                        escribe el código HEX si ya lo conoces.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <FormColorInput
                        id="primary_color"
                        v-model="form.primary_color"
                        label="Color primario"
                        :error="form.errors.primary_color"
                        tooltip="Color principal de la interfaz: botones, enlaces y elementos destacados para esta empresa."
                    />
                    <FormColorInput
                        id="secondary_color"
                        v-model="form.secondary_color"
                        label="Color secundario"
                        :error="form.errors.secondary_color"
                        tooltip="Color de apoyo, usado en elementos secundarios de la interfaz."
                    />
                </div>

                <div class="space-y-2">
                    <p class="text-xs font-medium text-muted-foreground">
                        Vista previa
                    </p>
                    <div
                        class="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/30 p-4"
                    >
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-medium"
                            :style="{
                                backgroundColor: previewPrimary,
                                color: previewPrimaryForeground,
                            }"
                        >
                            Botón primario
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-medium"
                            :style="{
                                backgroundColor: previewSecondary,
                                color: previewSecondaryForeground,
                            }"
                        >
                            Botón secundario
                        </button>
                        <Badge
                            :style="{
                                backgroundColor: previewPrimary,
                                color: previewPrimaryForeground,
                                borderColor: 'transparent',
                            }"
                        >
                            Etiqueta
                        </Badge>
                        <div
                            class="flex items-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm shadow-sm"
                        >
                            <span
                                class="size-2.5 rounded-full"
                                :style="{ backgroundColor: previewPrimary }"
                            />
                            {{ form.name || 'Nombre de la empresa' }}
                        </div>
                    </div>
                </div>
            </div>

            <Button type="submit" :disabled="form.processing"
                >Guardar cambios</Button
            >
        </form>
    </div>
</template>
