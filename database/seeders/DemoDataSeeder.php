<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@ventia.test'],
            [
                'name' => 'Superadministrador',
                'company_id' => null,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $superadmin->syncRoles(['Superadministrador']);

        $company = Company::firstOrCreate(
            ['tax_id' => 'VEN010101ABC'],
            [
                'name' => 'Ventia Demo',
                'legal_name' => 'Ventia Demo S.A. de C.V.',
                'address' => 'Av. Reforma 123, CDMX',
                'phone' => '5555555555',
                'email' => 'contacto@ventia-demo.test',
                'currency' => 'MXN',
                'timezone' => 'America/Mexico_City',
                'status' => Status::Active,
            ],
        );

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'SUC-001'],
            [
                'name' => 'Sucursal Centro',
                'address' => 'Av. Reforma 123, CDMX',
                'phone' => '5555555555',
                'status' => Status::Active,
            ],
        );

        $warehouse = Warehouse::firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'ALM-001'],
            [
                'company_id' => $company->id,
                'name' => 'Almacén Principal',
                'type' => WarehouseType::General,
                'allows_sale' => true,
                'status' => Status::Active,
            ],
        );

        CashRegister::firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'CAJA-001'],
            [
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'Caja 1',
                'has_cash_drawer' => true,
                'status' => Status::Active,
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@ventia-demo.test'],
            [
                'name' => 'Admin Ventia Demo',
                'company_id' => $company->id,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['Administrador de empresa']);
        $admin->branches()->syncWithoutDetaching([$branch->id]);

        $cashier = User::firstOrCreate(
            ['email' => 'cajero@ventia-demo.test'],
            [
                'name' => 'Cajero Demo',
                'company_id' => $company->id,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $cashier->syncRoles(['Cajero']);
        $cashier->branches()->syncWithoutDetaching([$branch->id]);
    }
}
