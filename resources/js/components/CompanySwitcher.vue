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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

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
    <DropdownMenu v-if="availableCompanies.length > 0">
        <div class="flex flex-col items-end gap-0.5">
            <span
                class="hidden text-[11px] leading-none font-medium tracking-wide text-muted-foreground uppercase sm:block"
            >
                Empresa activa
            </span>
            <Tooltip>
                <TooltipTrigger as-child>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="flex h-9 max-w-56 items-center gap-2 rounded-md border border-sidebar-border/70 bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            aria-label="Cambiar empresa activa"
                        >
                            <Building2
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span class="truncate">
                                {{
                                    activeCompany?.name ??
                                    'Selecciona una empresa'
                                }}
                            </span>
                            <ChevronsUpDown
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent
                    >Empresa activa (superadministrador)</TooltipContent
                >
            </Tooltip>
        </div>

        <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel>Cambiar empresa activa</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                v-for="company in availableCompanies"
                :key="company.id"
                class="flex items-center justify-between gap-2"
                @select="select(company.id)"
            >
                <span class="truncate">{{ company.name }}</span>
                <Check
                    v-if="company.id === activeCompany?.id"
                    class="size-4 shrink-0 text-primary"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
