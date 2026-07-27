<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    LayoutGrid,
    Package,
    Shield,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { index as rolesIndex } from '@/routes/roles';
import { index as branchesIndex } from '@/routes/settings/branches';
import { edit as companyEdit } from '@/routes/settings/company';
import { index as registersIndex } from '@/routes/settings/registers';
import { index as warehousesIndex } from '@/routes/settings/warehouses';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const { can } = usePermissions();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (can('companies.manage')) {
        items.push({
            title: 'Mi empresa',
            href: companyEdit(),
            icon: Building2,
        });
    }

    if (can('branches.manage')) {
        items.push({
            title: 'Sucursales',
            href: branchesIndex(),
            icon: Building2,
        });
    }

    if (can('warehouses.manage')) {
        items.push({
            title: 'Almacenes',
            href: warehousesIndex(),
            icon: Package,
        });
    }

    if (can('registers.manage')) {
        items.push({
            title: 'Cajas',
            href: registersIndex(),
            icon: CreditCard,
        });
    }

    if (can('users.manage')) {
        items.push({ title: 'Usuarios', href: usersIndex(), icon: Users });
    }

    if (can('roles.manage')) {
        items.push({ title: 'Roles', href: rolesIndex(), icon: Shield });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Principal" />
            <NavMain
                v-if="adminNavItems.length > 0"
                :items="adminNavItems"
                label="Administración"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
