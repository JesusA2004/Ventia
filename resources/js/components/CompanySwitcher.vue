<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Building2, ChevronsUpDown, Check } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();

const activeCompany = computed(() => page.props.activeCompany);
const availableCompanies = computed(() => page.props.availableCompanies ?? []);

function select(companyId: number) {
    if (companyId === activeCompany.value?.id) {
        return;
    }

    router.post(
        '/company-selection',
        { company_id: companyId },
        { preserveScroll: true },
    );
}
</script>

<template>
    <div v-if="availableCompanies.length > 0" class="flex flex-col items-end gap-0.5">
        <span
            class="hidden text-[11px] leading-none font-medium tracking-wide text-muted-foreground uppercase sm:block"
        >
            Empresa activa
        </span>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <button
                    type="button"
                    class="flex h-9 max-w-56 items-center gap-2 rounded-md border border-sidebar-border/70 bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    aria-label="Cambiar empresa activa"
                    title="Empresa activa (superadministrador)"
                >
                    <Building2
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                    <span class="truncate">
                        {{
                            activeCompany?.name ?? 'Selecciona una empresa'
                        }}
                    </span>
                    <ChevronsUpDown
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" class="w-64">
                <DropdownMenuLabel>Cambiar empresa activa</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-for="company in availableCompanies"
                    :key="company.id"
                    :as-child="true"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-2"
                        @click="select(company.id)"
                    >
                        <span class="truncate">{{ company.name }}</span>
                        <Check
                            v-if="company.id === activeCompany?.id"
                            class="size-4 shrink-0 text-primary"
                        />
                    </button>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
