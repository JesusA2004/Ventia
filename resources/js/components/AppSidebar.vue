<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Archive,
    ArrowLeftRight,
    Building2,
    ClipboardList,
    CreditCard,
    LayoutGrid,
    ListChecks,
    Package,
    PackageSearch,
    Percent,
    Receipt,
    Shield,
    ShoppingBag,
    SlidersHorizontal,
    Tags,
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
import { index as brandsIndex } from '@/routes/catalog/brands';
import { index as categoriesIndex } from '@/routes/catalog/categories';
import { index as priceListsIndex } from '@/routes/catalog/price-lists';
import { index as taxesIndex } from '@/routes/catalog/taxes';
import { index as unitsIndex } from '@/routes/catalog/units';
import { create as adjustmentsCreate } from '@/routes/inventory/adjustments';
import { index as balancesIndex } from '@/routes/inventory/balances';
import { index as countsIndex } from '@/routes/inventory/counts';
import { index as kardexIndex } from '@/routes/inventory/kardex';
import { index as lotsIndex } from '@/routes/inventory/lots';
import { index as transfersIndex } from '@/routes/inventory/transfers';
import { index as productsIndex } from '@/routes/products';
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

const catalogNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (can('products.view')) {
        items.push({
            title: 'Productos',
            href: productsIndex(),
            icon: ShoppingBag,
        });
    }

    if (can('categories.manage')) {
        items.push({
            title: 'Categorías',
            href: categoriesIndex(),
            icon: Tags,
        });
    }

    if (can('brands.manage')) {
        items.push({ title: 'Marcas', href: brandsIndex(), icon: Package });
    }

    if (can('units.manage')) {
        items.push({
            title: 'Unidades',
            href: unitsIndex(),
            icon: SlidersHorizontal,
        });
    }

    if (can('taxes.manage')) {
        items.push({ title: 'Impuestos', href: taxesIndex(), icon: Percent });
    }

    if (can('price-lists.manage')) {
        items.push({
            title: 'Listas de precios',
            href: priceListsIndex(),
            icon: Receipt,
        });
    }

    return items;
});

const inventoryNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (can('inventory.view')) {
        items.push({
            title: 'Existencias',
            href: balancesIndex(),
            icon: PackageSearch,
        });
    }

    if (can('inventory.kardex')) {
        items.push({
            title: 'Kardex',
            href: kardexIndex(),
            icon: ClipboardList,
        });
    }

    if (can('inventory.adjust')) {
        items.push({
            title: 'Ajustes',
            href: adjustmentsCreate(),
            icon: ListChecks,
        });
    }

    if (can('inventory.transfer')) {
        items.push({
            title: 'Transferencias',
            href: transfersIndex(),
            icon: ArrowLeftRight,
        });
    }

    if (can('inventory.count')) {
        items.push({
            title: 'Conteos físicos',
            href: countsIndex(),
            icon: ClipboardList,
        });
    }

    if (can('inventory.view')) {
        items.push({
            title: 'Lotes y caducidades',
            href: lotsIndex(),
            icon: Archive,
        });
    }

    return items;
});

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
                v-if="catalogNavItems.length > 0"
                :items="catalogNavItems"
                label="Catálogo"
            />
            <NavMain
                v-if="inventoryNavItems.length > 0"
                :items="inventoryNavItems"
                label="Inventario"
            />
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
