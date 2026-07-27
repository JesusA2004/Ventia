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
        'products' => ['products.view', 'products.view-costs', 'products.create', 'products.update', 'products.delete'],
        'prices' => ['prices.edit'],
        'discounts' => ['discounts.apply', 'discounts.authorize'],
        'promotions' => ['promotions.manage'],
        'sales' => ['sales.view', 'sales.create', 'sales.cancel', 'sales.reprint-ticket'],
        'cash' => ['cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit'],
        'inventory' => ['inventory.adjust', 'inventory.transfer'],
        'reports' => ['reports.view', 'reports.export'],
        'audit' => ['audit.view'],
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
            'products.view', 'products.view-costs', 'products.create', 'products.update', 'products.delete',
            'prices.edit', 'discounts.apply', 'discounts.authorize', 'promotions.manage',
            'sales.view', 'sales.create', 'sales.cancel', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
            'inventory.adjust', 'inventory.transfer',
            'reports.view', 'reports.export', 'audit.view',
        ],
        'Gerente' => [
            'branches.access-all', 'warehouses.manage', 'registers.manage',
            'products.view', 'products.view-costs', 'products.create', 'products.update',
            'prices.edit', 'discounts.apply', 'discounts.authorize', 'promotions.manage',
            'sales.view', 'sales.create', 'sales.cancel', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
            'inventory.adjust', 'inventory.transfer',
            'reports.view', 'reports.export', 'audit.view',
        ],
        'Encargado de sucursal' => [
            'warehouses.manage', 'registers.manage',
            'products.view', 'products.view-costs', 'products.update',
            'discounts.apply', 'discounts.authorize',
            'sales.view', 'sales.create', 'sales.cancel', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
            'inventory.adjust', 'inventory.transfer',
            'reports.view',
        ],
        'Supervisor' => [
            'products.view', 'discounts.apply',
            'sales.view', 'sales.create', 'sales.reprint-ticket',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
            'inventory.adjust',
            'reports.view',
        ],
        'Cajero' => [
            'products.view',
            'sales.view', 'sales.create', 'sales.reprint-ticket',
            'discounts.apply',
            'cash.open', 'cash.close', 'cash.register-withdrawal', 'cash.register-deposit',
        ],
        'Almacenista' => [
            'products.view',
            'inventory.adjust', 'inventory.transfer',
            'reports.view',
        ],
        'Contabilidad' => [
            'products.view', 'products.view-costs',
            'reports.view', 'reports.export', 'audit.view',
        ],
        'Consulta' => [
            'products.view', 'sales.view', 'reports.view',
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
