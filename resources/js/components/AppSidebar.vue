<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Archive,
    Award,
    BadgeHelp,
    Banknote,
    Building2,
    ClipboardCheck,
    ClipboardList,
    CreditCard,
    HandCoins,
    HelpCircle,
    LayoutGrid,
    LineChart,
    MapPin,
    Package,
    PackageSearch,
    Percent,
    ReceiptText,
    Shield,
    ShoppingBag,
    ShoppingCart,
    SlidersHorizontal,
    Tags,
    Users,
    Wallet,
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
import cash from '@/routes/cash';
import { index as brandsIndex } from '@/routes/catalog/brands';
import { index as categoriesIndex } from '@/routes/catalog/categories';
import { index as taxesIndex } from '@/routes/catalog/taxes';
import { index as unitsIndex } from '@/routes/catalog/units';
import { index as customersIndex } from '@/routes/customers';
import { gettingStarted, guide } from '@/routes/help';
import { index as balancesIndex } from '@/routes/inventory/balances';
import { index as countsIndex } from '@/routes/inventory/counts';
import { index as kardexIndex } from '@/routes/inventory/kardex';
import { index as lotsIndex } from '@/routes/inventory/lots';
import { index as transfersIndex } from '@/routes/inventory/transfers';
import { index as paymentMethodsIndex } from '@/routes/payment-methods';
import { index as posIndex } from '@/routes/pos';
import { index as productsIndex } from '@/routes/products';
import { index as reportsIndex } from '@/routes/reports';
import { index as rolesIndex } from '@/routes/roles';
import salesRoutes from '@/routes/sales';
import { index as branchesIndex } from '@/routes/settings/branches';
import { edit as companyEdit } from '@/routes/settings/company';
import { index as registersIndex } from '@/routes/settings/registers';
import { index as warehousesIndex } from '@/routes/settings/warehouses';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const { can } = usePermissions();

/**
 * Grouping follows the simplified target layout: Principal, Operación,
 * Catálogo, Inventario, Análisis, Administración, Ayuda. "Listas de precios"
 * is deliberately not linked here (business doesn't need pricing tiers yet —
 * see item 13 of the audit); the route, models and price history stay fully
 * functional, just not surfaced in navigation.
 */
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    ];

    if (can('pos.access')) {
        items.push({
            title: 'Punto de venta',
            href: posIndex(),
            icon: ShoppingCart,
        });
    }

    return items;
});

const operationNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (can('cash.open')) {
        items.push({
            title: 'Abrir caja',
            href: cash.sessions.create(),
            icon: Wallet,
        });
    }

    if (can('cash.view') || can('cash.open')) {
        items.push({
            title: 'Sesiones de caja',
            href: cash.sessions.index(),
            icon: Wallet,
        });
    }

    if (can('cash.approve-close') || can('cash.receive-handover')) {
        items.push({
            title: 'Entregas pendientes',
            href: cash.handovers.index(),
            icon: HandCoins,
        });
    }

    if (can('sales.view')) {
        items.push({
            title: 'Ventas',
            href: salesRoutes.index(),
            icon: ReceiptText,
        });
    }

    if (can('customers.view')) {
        items.push({ title: 'Clientes', href: customersIndex(), icon: Users });
    }

    return items;
});

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
        items.push({ title: 'Marcas', href: brandsIndex(), icon: Award });
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
            title: 'Movimientos',
            href: kardexIndex(),
            icon: ClipboardList,
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
            title: 'Conteos',
            href: countsIndex(),
            icon: ClipboardCheck,
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

const analysisNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (can('reports.view')) {
        items.push({
            title: 'Reportes',
            href: reportsIndex(),
            icon: LineChart,
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
            icon: MapPin,
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

    if (can('payment-methods.manage')) {
        items.push({
            title: 'Métodos de pago',
            href: paymentMethodsIndex(),
            icon: Banknote,
        });
    }

    if (can('users.manage')) {
        items.push({ title: 'Usuarios', href: usersIndex(), icon: Users });
    }

    if (can('roles.manage')) {
        items.push({
            title: 'Roles y permisos',
            href: rolesIndex(),
            icon: Shield,
        });
    }

    return items;
});

const helpNavItems = computed<NavItem[]>(() => [
    { title: 'Primeros pasos', href: gettingStarted(), icon: BadgeHelp },
    { title: 'Guía', href: guide(), icon: HelpCircle },
]);
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
                v-if="operationNavItems.length > 0"
                :items="operationNavItems"
                label="Operación"
            />
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
                v-if="analysisNavItems.length > 0"
                :items="analysisNavItems"
                label="Análisis"
            />
            <NavMain
                v-if="adminNavItems.length > 0"
                :items="adminNavItems"
                label="Administración"
            />
            <NavMain :items="helpNavItems" label="Ayuda" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
