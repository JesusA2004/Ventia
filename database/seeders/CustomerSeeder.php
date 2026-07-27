<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\Status;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // The "Público general" customer is created automatically by
        // Company::booted() whenever a company is created — this seeder only
        // adds a couple of named demo customers on top of it.
        Company::query()->each(function (Company $company) {
            Customer::query()->firstOrCreate(
                ['company_id' => $company->id, 'tax_id' => 'XAXX010101000'],
                [
                    'customer_type' => CustomerType::Individual,
                    'name' => 'María González',
                    'phone' => '5551234567',
                    'email' => 'maria.gonzalez@example.com',
                    'status' => Status::Active,
                ],
            );

            Customer::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Abarrotes La Esquina S.A. de C.V.'],
                [
                    'customer_type' => CustomerType::Business,
                    'legal_name' => 'Abarrotes La Esquina S.A. de C.V.',
                    'tax_id' => 'ALE010101AB1',
                    'phone' => '5559876543',
                    'email' => 'compras@laesquina.example.com',
                    'credit_limit' => '5000.0000',
                    'status' => Status::Active,
                ],
            );
        });
    }
}
