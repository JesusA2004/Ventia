<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Inventory\InventoryBalanceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:rebuild-inventory-balances {--company= : Recalculate a single company by ID; omit to rebuild every company}')]
#[Description('Recomputes inventory_balances from inventory_movements, in case balances have drifted')]
class RebuildInventoryBalances extends Command
{
    public function handle(InventoryBalanceService $balances): int
    {
        $companyId = $this->option('company');

        $companies = $companyId
            ? Company::query()->whereKey($companyId)->get()
            : Company::query()->get();

        if ($companies->isEmpty()) {
            $this->error('No matching company found.');

            return self::FAILURE;
        }

        foreach ($companies as $company) {
            $this->info("Rebuilding inventory balances for company #{$company->id} ({$company->name})...");
            $balances->rebuildForCompany($company->id);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
