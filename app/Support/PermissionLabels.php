<?php

namespace App\Support;

/**
 * Spanish display labels for Spatie permissions. Deliberately separate from
 * the permission slugs themselves (database/seeders/RolesAndPermissionsSeeder.php)
 * so those internal identifiers never change — only how they're presented
 * in Roles/Edit. A slug missing from here just falls back to itself, so
 * adding a new permission never breaks this screen.
 */
final class PermissionLabels
{
    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'companies' => 'Empresa',
            'branches' => 'Sucursales',
            'warehouses' => 'Almacenes',
            'registers' => 'Cajas',
            'users' => 'Usuarios',
            'roles' => 'Roles y permisos',
            'settings' => 'Configuración',
            'categories' => 'Categorías',
            'brands' => 'Marcas',
            'units' => 'Unidades de medida',
            'taxes' => 'Impuestos',
            'price-lists' => 'Listas de precios',
            'products' => 'Productos',
            'prices' => 'Precios',
            'costs' => 'Costos',
            'discounts' => 'Descuentos',
            'promotions' => 'Promociones',
            'coupons' => 'Cupones',
            'customers' => 'Clientes',
            'payment-methods' => 'Métodos de pago',
            'pos' => 'Punto de venta',
            'sales' => 'Ventas',
            'cash' => 'Caja',
            'inventory' => 'Inventario',
            'reports' => 'Reportes',
            'audit' => 'Auditoría',
            'dashboard' => 'Panel principal',
        ];
    }

    public static function group(string $key): string
    {
        return self::groups()[$key] ?? $key;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'companies.manage' => 'Administrar datos de la empresa',
            'branches.manage' => 'Crear y editar sucursales',
            'branches.access-all' => 'Acceder a todas las sucursales',
            'warehouses.manage' => 'Crear y editar almacenes',
            'registers.manage' => 'Crear y editar cajas',
            'users.manage' => 'Crear y editar usuarios',
            'roles.manage' => 'Administrar roles y permisos',
            'settings.manage' => 'Administrar configuración general',
            'categories.manage' => 'Crear y editar categorías',
            'brands.manage' => 'Crear y editar marcas',
            'units.manage' => 'Crear y editar unidades de medida',
            'taxes.manage' => 'Crear y editar impuestos',
            'price-lists.manage' => 'Crear y editar listas de precios',
            'products.view' => 'Ver productos',
            'products.view-costs' => 'Ver costos de productos',
            'products.create' => 'Crear productos',
            'products.update' => 'Editar productos',
            'products.delete' => 'Eliminar productos',
            'prices.view' => 'Ver precios',
            'prices.edit' => 'Cambiar precios de venta',
            'prices.view-history' => 'Ver historial de precios',
            'costs.view' => 'Ver costos',
            'costs.edit' => 'Cambiar costos',
            'discounts.apply' => 'Aplicar descuentos en ventas',
            'discounts.authorize' => 'Autorizar descuentos que exceden el límite',
            'promotions.manage' => 'Crear y editar promociones',
            'coupons.manage' => 'Crear y editar cupones',
            'customers.view' => 'Ver clientes',
            'customers.create' => 'Crear clientes',
            'customers.update' => 'Editar clientes',
            'customers.delete' => 'Eliminar clientes',
            'payment-methods.manage' => 'Crear y editar métodos de pago',
            'pos.access' => 'Acceder al punto de venta',
            'sales.view' => 'Ver ventas',
            'sales.create' => 'Registrar ventas',
            'sales.suspend' => 'Suspender ventas',
            'sales.cancel' => 'Cancelar ventas',
            'sales.return' => 'Registrar devoluciones',
            'sales.reprint-ticket' => 'Reimprimir tickets',
            'cash.open' => 'Abrir caja',
            'cash.close' => 'Cerrar caja',
            'cash.view' => 'Ver sesiones de caja',
            'cash.register-deposit' => 'Registrar depósitos de efectivo',
            'cash.register-withdrawal' => 'Registrar retiros de efectivo',
            'cash.register-expense' => 'Registrar gastos de caja',
            'cash.force-close' => 'Forzar el cierre de una caja',
            'cash.approve-open' => 'Aprobar apertura de caja',
            'cash.approve-close' => 'Aprobar cierre de caja',
            'cash.receive-handover' => 'Recibir corte/entrega de caja',
            'cash.view-differences' => 'Ver diferencias de caja',
            'inventory.view' => 'Ver inventario',
            'inventory.adjust' => 'Realizar ajustes de inventario',
            'inventory.transfer' => 'Realizar transferencias de inventario',
            'inventory.count' => 'Realizar conteos físicos',
            'inventory.view-costs' => 'Ver costos de inventario',
            'inventory.kardex' => 'Ver kardex de movimientos',
            'inventory.rebuild' => 'Reconstruir saldos de inventario',
            'reports.view' => 'Ver reportes',
            'reports.export' => 'Exportar reportes',
            'audit.view' => 'Ver auditoría del sistema',
            'dashboard.view' => 'Ver panel principal',
        ];
    }

    public static function label(string $permission): string
    {
        return self::labels()[$permission] ?? $permission;
    }

    /**
     * Extra context for permissions whose impact isn't obvious from the
     * label alone — shown as a tooltip next to the checkbox.
     *
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            'discounts.authorize' => 'Permite autorizar descuentos que superen el porcentaje máximo configurado sin autorización.',
            'cash.force-close' => 'Permite cerrar la caja de otro usuario, por ejemplo si olvidó cerrarla al final del turno.',
            'inventory.rebuild' => 'Recalcula los saldos de inventario a partir del historial de movimientos. Úsalo solo si sospechas que los saldos están desincronizados.',
            'branches.access-all' => 'Sin este permiso, el usuario solo puede operar en las sucursales que tenga asignadas.',
            'sales.cancel' => 'Permite cancelar una venta ya completada, revirtiendo inventario y caja.',
            'audit.view' => 'Acceso al registro de auditoría de acciones de todos los usuarios.',
        ];
    }

    public static function description(string $permission): ?string
    {
        return self::descriptions()[$permission] ?? null;
    }
}
