<?php

namespace App\Services\Sales;

use App\Models\Company;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

/**
 * Generates the next sequential folio for a company. Locking the Company row
 * (rather than "the last sale row", which doesn't exist yet for the very
 * first sale and shifts under concurrent inserts) serializes every folio
 * generation for that company, so two concurrent checkouts can never be
 * handed the same folio. Must be called from within the same transaction
 * that will insert the Sale row.
 */
class SaleFolioService
{
    public function next(int $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            Company::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();

            $count = Sale::withoutGlobalScopes()->where('company_id', $companyId)->count();

            return 'V-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
        });
    }

    public function nextReturnFolio(int $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            Company::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();

            $count = SaleReturn::withoutGlobalScopes()->where('company_id', $companyId)->count();

            return 'DEV-'.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
        });
    }
}
