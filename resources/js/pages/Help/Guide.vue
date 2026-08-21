<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { gettingStarted } from '@/routes/help';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Guía', href: '#' }],
    },
});

const roles = [
    {
        name: 'Administrador de empresa',
        description:
            'Control total de la empresa: configuración, sucursales, usuarios, roles, catálogo, ventas, caja, inventario y reportes.',
    },
    {
        name: 'Gerente',
        description:
            'Igual que Administrador de empresa, sin gestión de la empresa ni de usuarios/roles.',
    },
    {
        name: 'Encargado de sucursal',
        description:
            'Administra almacenes y cajas de su sucursal, vende, abre/cierra caja y aprueba entregas de caja.',
    },
    {
        name: 'Supervisor',
        description:
            'Aprueba aperturas/cierres de caja y recibe entregas de turno; vende y ajusta inventario.',
    },
    {
        name: 'Cajero',
        description: 'Vende en el punto de venta y abre/cierra su propia caja.',
    },
    {
        name: 'Almacenista',
        description:
            'Gestiona inventario: ajustes, transferencias, conteos físicos y kardex.',
    },
    {
        name: 'Contabilidad',
        description:
            'Consulta costos, precios, ventas, caja e inventario; exporta reportes y revisa auditoría.',
    },
    {
        name: 'Consulta',
        description:
            'Solo lectura de productos, clientes, ventas, precios e inventario.',
    },
];

const modules = [
    {
        name: 'Punto de venta',
        description:
            'Vende productos, cobra y emite el ticket. Requiere una caja abierta si la empresa lo exige.',
    },
    {
        name: 'Caja',
        description:
            'Abre y cierra turnos de caja, registra depósitos/retiros/gastos y consulta el historial de sesiones.',
    },
    {
        name: 'Ventas',
        description:
            'Historial de ventas, tickets, cancelaciones y devoluciones.',
    },
    {
        name: 'Clientes',
        description: 'Catálogo de clientes y su historial de compras.',
    },
    {
        name: 'Promociones y cupones',
        description:
            'Promociones: reglas de descuento que se aplican solas cuando la venta cumple las condiciones (sucursal, producto/categoría, compra mínima). Cupones: códigos que el cajero captura en el POS. Solo aplica la mejor promoción automática y, si el cupón es combinable, se suma; si no, el cupón la reemplaza.',
    },
    {
        name: 'Productos',
        description:
            'Catálogo de productos, variantes, categorías, marcas, unidades e impuestos.',
    },
    {
        name: 'Existencias',
        description: 'Stock actual por producto y almacén.',
    },
    {
        name: 'Movimientos',
        description:
            'Historial completo de entradas y salidas de inventario, incluyendo ajustes manuales.',
    },
    {
        name: 'Transferencias',
        description:
            'Mueve inventario entre almacenes: solicitud, aprobación, envío y recepción.',
    },
    {
        name: 'Conteos',
        description:
            'Conteo físico de inventario y aplicación de diferencias contra el sistema.',
    },
    {
        name: 'Lotes',
        description: 'Trazabilidad por lote y alertas de caducidad.',
    },
    {
        name: 'Reportes',
        description:
            'Ventas, caja e inventario del período, con exportación a CSV.',
    },
];
</script>

<template>
    <Head title="Guía" />

    <div class="flex flex-col gap-6">
        <PageHeader
            title="Guía de roles, permisos y módulos"
            description="Referencia rápida de qué puede hacer cada rol y para qué sirve cada módulo."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="gettingStarted()"
                        >Volver a primeros pasos</Link
                    >
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-3">
            <h2 class="text-sm font-semibold">Roles</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <Card v-for="role in roles" :key="role.name">
                    <CardHeader>
                        <CardTitle class="text-base">{{ role.name }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{ role.description }}
                    </CardContent>
                </Card>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-sm font-semibold">Módulos</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="module in modules" :key="module.name">
                    <CardHeader
                        class="flex-row items-center justify-between space-y-0"
                    >
                        <CardTitle class="text-base">{{
                            module.name
                        }}</CardTitle>
                        <Badge variant="outline">Módulo</Badge>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{ module.description }}
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
