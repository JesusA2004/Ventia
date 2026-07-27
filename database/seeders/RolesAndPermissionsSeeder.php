<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * All granular permissions in the system, grouped by module.
     * Add new permissions here as new modules/phases are built.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSIONS = [
        'companies' => ['companies.manage'],
        'branches' => ['branches.manage', 'branches.access-all'],
        'warehouses' => ['warehouses.manage'],
        'registers' => ['registers.manage'],
        'users' => ['users.manage'],
        'roles' => ['roles.manage'],
        'settings' => ['settings.manage'],
        'categories' => ['categories.manage'],
        'brands' => ['brands.manage'],
        'units' => ['units.manage'],
        'taxes' => ['taxes.manage'],
        'price-lists' => ['price-lists.manage'],
        'products' => ['products.view', 'products.view-costs', 'products.create', 'products.update', 'products.delete'],
        'prices' => ['prices.view', 'prices.edit', 'prices.view-history'],
        'costs' => ['costs.view', 'costs.edit'],
        'discounts' => ['discounts.apply', 'discounts.authorize'],
        'promotions' => ['promotions.manage'],
        'customers' => ['customers.view', 'customers.create', 'customers.update', 'customers.delete'],
        'payment-methods' => ['payment-methods.manage'],
        'pos' => ['pos.access'],
        'sales' => [
            'sales.view', 'sales.create', 'sales.suspend', 'sales.cancel', 'sales.return', 'sales.reprint-ticket',
        ],
        'cash' => [
            'cash.open', 'cash.close', 'cash.view', 'cash.register-deposit', 'cash.register-withdrawal',
            'cash.register-expense', 'cash.force-close',
        ],
        'inventory' => [
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count',
            'inventory.view-costs', 'inventory.kardex', 'inventory.rebuild',
        ],
        'reports' => ['reports.view', 'reports.export'],
        'audit' => ['audit.view'],
        'dashboard' => ['dashboard.view'],
    ];

    /**
     * Permissions granted to each role, beyond Superadministrador (which
     * bypasses every check via Gate::before in AppServiceProvider).
     *
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        'Administrador de empresa' => [
            'companies.manage', 'branches.manage', 'branches.access-all', 'warehouses.manage', 'registers.manage',
            'users.manage', 'roles.manage', 'settings.manage',
            'categories.manage', 'brands.manage', 'units.manage', 'taxes.manage', 'price-lists.manage',
            'products.view', 'products.view-costs', 'products.create', 'products.update', 'products.delete',
            'prices.view', 'prices.edit', 'prices.view-history', 'costs.view', 'costs.edit',
            'discounts.apply', 'discounts.authorize', 'promotions.manage',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'payment-methods.manage', 'pos.access',
            'sales.view', 'sales.create', 'sales.suspend', 'sales.cancel', 'sales.return', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.view', 'cash.register-deposit', 'cash.register-withdrawal',
            'cash.register-expense', 'cash.force-close',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count',
            'inventory.view-costs', 'inventory.kardex', 'inventory.rebuild',
            'reports.view', 'reports.export', 'audit.view', 'dashboard.view',
        ],
        'Gerente' => [
            'branches.access-all', 'warehouses.manage', 'registers.manage',
            'categories.manage', 'brands.manage', 'units.manage', 'taxes.manage', 'price-lists.manage',
            'products.view', 'products.view-costs', 'products.create', 'products.update',
            'prices.view', 'prices.edit', 'prices.view-history', 'costs.view', 'costs.edit',
            'discounts.apply', 'discounts.authorize', 'promotions.manage',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'payment-methods.manage', 'pos.access',
            'sales.view', 'sales.create', 'sales.suspend', 'sales.cancel', 'sales.return', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.view', 'cash.register-deposit', 'cash.register-withdrawal',
            'cash.register-expense', 'cash.force-close',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count',
            'inventory.view-costs', 'inventory.kardex', 'inventory.rebuild',
            'reports.view', 'reports.export', 'audit.view', 'dashboard.view',
        ],
        'Encargado de sucursal' => [
            'warehouses.manage', 'registers.manage',
            'products.view', 'products.view-costs', 'products.update',
            'discounts.apply', 'discounts.authorize',
            'customers.view', 'customers.create', 'customers.update',
            'pos.access',
            'sales.view', 'sales.create', 'sales.suspend', 'sales.cancel', 'sales.return', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.view', 'cash.register-deposit', 'cash.register-withdrawal', 'cash.register-expense',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count', 'inventory.kardex',
            'reports.view', 'dashboard.view',
        ],
        'Supervisor' => [
            'products.view', 'discounts.apply',
            'customers.view', 'customers.create',
            'pos.access',
            'sales.view', 'sales.create', 'sales.suspend', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
            'inventory.view', 'inventory.adjust', 'inventory.kardex',
            'reports.view', 'dashboard.view',
        ],
        'Cajero' => [
            'products.view',
            'customers.view', 'customers.create',
            'pos.access',
            'sales.view', 'sales.create', 'sales.suspend', 'sales.reprint-ticket',
            'discounts.apply',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
            'dashboard.view',
        ],
        'Almacenista' => [
            'products.view',
            'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count',
            'inventory.view-costs', 'inventory.kardex',
            'reports.view', 'dashboard.view',
        ],
        'Contabilidad' => [
            'products.view', 'products.view-costs',
            'prices.view', 'prices.view-history', 'costs.view', 'costs.edit',
            'customers.view', 'sales.view', 'cash.view',
            'inventory.view', 'inventory.view-costs', 'inventory.kardex',
            'reports.view', 'reports.export', 'audit.view', 'dashboard.view',
        ],
        'Consulta' => [
            'products.view', 'customers.view', 'sales.view', 'reports.view', 'prices.view', 'inventory.view', 'dashboard.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }

        Role::findOrCreate('Superadministrador', 'web');

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
