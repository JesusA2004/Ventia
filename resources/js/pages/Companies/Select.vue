<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Company } from '@/types';

defineProps<{
    companies: Company[];
}>();

function select(companyId: number) {
    router.post('/company-selection', { company_id: companyId });
}
</script>

<template>
    <Head title="Selecciona una empresa" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6 py-10">
        <PageHeader
            title="Selecciona una empresa"
            description="Eres superadministrador: elige la empresa sobre la que quieres trabajar. Podrás cambiarla en cualquier momento desde el encabezado."
        />

        <p
            v-if="companies.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            No hay empresas activas registradas todavía.
        </p>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <Card
                v-for="company in companies"
                :key="company.id"
                class="cursor-pointer transition-colors hover:border-primary"
                role="button"
                tabindex="0"
                @click="select(company.id)"
                @keydown.enter="select(company.id)"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Building2 class="size-5 text-muted-foreground" />
                        {{ company.name }}
                    </CardTitle>
                    <CardDescription v-if="company.legal_name">
                        {{ company.legal_name }}
                    </CardDescription>
                </CardHeader>
                <CardContent
                    v-if="company.address || company.email"
                    class="text-sm text-muted-foreground"
                >
                    <p v-if="company.address">{{ company.address }}</p>
                    <p v-if="company.email">{{ company.email }}</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
