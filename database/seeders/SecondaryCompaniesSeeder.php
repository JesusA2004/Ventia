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

/**
 * DemoDataSeeder only ever creates a single company ("Ventia Demo"), which
 * leaves the superadmin company switcher with nothing to switch to. This
 * seeder adds a couple more standalone companies (own branch, warehouse,
 * cash register and admin user) purely so multiempresa can actually be
 * exercised end to end. Idempotent via firstOrCreate — safe to re-run.
 */
class SecondaryCompaniesSeeder extends Seeder
{
    public function run(): void
    {
        $this->createCompany(
            taxId: 'VEN020202DEF',
            name: 'Empresa Demo 2',
            legalName: 'Empresa Demo 2 S.A. de C.V.',
            address: 'Av. Insurgentes Sur 456, CDMX',
            email: 'contacto@empresademo2.test',
            currency: 'MXN',
            branchCode: 'SUC-001',
            branchName: 'Sucursal Principal',
            warehouseCode: 'ALM-001',
            warehouseName: 'Almacén Principal',
            cashCode: 'CAJA-001',
            adminEmail: 'admin@empresademo2.test',
            adminName: 'Admin Empresa Demo 2',
        );

        $this->createCompany(
            taxId: 'VEN030303GHI',
            name: 'Empresa Demo 3',
            legalName: 'Empresa Demo 3 S.A. de C.V.',
            address: 'Blvd. Díaz Ordaz 789, Monterrey',
            email: 'contacto@empresademo3.test',
            currency: 'MXN',
            branchCode: 'SUC-001',
            branchName: 'Sucursal Monterrey',
            warehouseCode: 'ALM-001',
            warehouseName: 'Almacén Monterrey',
            cashCode: 'CAJA-001',
            adminEmail: 'admin@empresademo3.test',
            adminName: 'Admin Empresa Demo 3',
        );
    }

    private function createCompany(
        string $taxId,
        string $name,
        string $legalName,
        string $address,
        string $email,
        string $currency,
        string $branchCode,
        string $branchName,
        string $warehouseCode,
        string $warehouseName,
        string $cashCode,
        string $adminEmail,
        string $adminName,
    ): void {
        $company = Company::firstOrCreate(
            ['tax_id' => $taxId],
            [
                'name' => $name,
                'legal_name' => $legalName,
                'address' => $address,
                'phone' => '5555555556',
                'email' => $email,
                'currency' => $currency,
                'timezone' => 'America/Mexico_City',
                'status' => Status::Active,
            ],
        );

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => $branchCode],
            [
                'name' => $branchName,
                'address' => $address,
                'phone' => '5555555556',
                'status' => Status::Active,
            ],
        );

        $warehouse = Warehouse::firstOrCreate(
            ['branch_id' => $branch->id, 'code' => $warehouseCode],
            [
                'company_id' => $company->id,
                'name' => $warehouseName,
                'type' => WarehouseType::General,
                'allows_sale' => true,
                'status' => Status::Active,
            ],
        );

        CashRegister::firstOrCreate(
            ['branch_id' => $branch->id, 'code' => $cashCode],
            [
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'Caja 1',
                'has_cash_drawer' => true,
                'status' => Status::Active,
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'company_id' => $company->id,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['Administrador de empresa']);
        $admin->branches()->syncWithoutDetaching([$branch->id]);
    }
}
