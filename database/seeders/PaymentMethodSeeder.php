<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodType;
use App\Enums\Status;
use App\Models\Company;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company) {
            $methods = [
                ['name' => 'Efectivo', 'code' => 'CASH', 'type' => PaymentMethodType::Cash, 'requires_reference' => false, 'opens_cash_drawer' => true, 'affects_cash' => true, 'allows_change' => true, 'sort_order' => 1],
                ['name' => 'Tarjeta de débito', 'code' => 'DEBIT', 'type' => PaymentMethodType::CardDebit, 'requires_reference' => true, 'opens_cash_drawer' => false, 'affects_cash' => false, 'allows_change' => false, 'sort_order' => 2],
                ['name' => 'Tarjeta de crédito', 'code' => 'CREDIT', 'type' => PaymentMethodType::CardCredit, 'requires_reference' => true, 'opens_cash_drawer' => false, 'affects_cash' => false, 'allows_change' => false, 'sort_order' => 3],
                ['name' => 'Transferencia', 'code' => 'TRANSFER', 'type' => PaymentMethodType::Transfer, 'requires_reference' => true, 'opens_cash_drawer' => false, 'affects_cash' => false, 'allows_change' => false, 'sort_order' => 4],
                ['name' => 'Vale', 'code' => 'VOUCHER', 'type' => PaymentMethodType::Voucher, 'requires_reference' => true, 'opens_cash_drawer' => false, 'affects_cash' => false, 'allows_change' => false, 'sort_order' => 5],
                ['name' => 'Crédito del cliente', 'code' => 'CREDIT_CLIENT', 'type' => PaymentMethodType::CustomerCredit, 'requires_reference' => false, 'opens_cash_drawer' => false, 'affects_cash' => false, 'allows_change' => false, 'sort_order' => 6],
                ['name' => 'Otro', 'code' => 'OTHER', 'type' => PaymentMethodType::Other, 'requires_reference' => false, 'opens_cash_drawer' => false, 'affects_cash' => false, 'allows_change' => false, 'sort_order' => 7],
            ];

            foreach ($methods as $method) {
                PaymentMethod::query()->firstOrCreate(
                    ['company_id' => $company->id, 'code' => $method['code']],
                    [...$method, 'company_id' => $company->id, 'status' => Status::Active],
                );
            }
        });
    }
}
