<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves operational settings for a company, with optional per-branch
 * overrides falling back to company-level values and finally to
 * config('pos.defaults'). Never overwrite a value silently: callers always
 * go through set() so changes are explicit and cache-invalidated.
 */
class SettingsService
{
    public function get(int $companyId, string $key, ?int $branchId = null): mixed
    {
        $values = $this->all($companyId, $branchId);

        return $values[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(int $companyId, ?int $branchId = null): array
    {
        $cacheKey = "settings:{$companyId}:".($branchId ?? 'company');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($companyId, $branchId) {
            $values = config('pos.defaults', []);

            $companySettings = Setting::query()
                ->where('company_id', $companyId)
                ->whereNull('branch_id')
                ->pluck('value', 'key');

            foreach ($companySettings as $key => $value) {
                $values[$key] = $value;
            }

            if ($branchId !== null) {
                $branchSettings = Setting::query()
                    ->where('company_id', $companyId)
                    ->where('branch_id', $branchId)
                    ->pluck('value', 'key');

                foreach ($branchSettings as $key => $value) {
                    $values[$key] = $value;
                }
            }

            return $values;
        });
    }

    public function set(int $companyId, string $key, mixed $value, ?int $branchId = null): Setting
    {
        $setting = Setting::query()->updateOrCreate(
            ['company_id' => $companyId, 'branch_id' => $branchId, 'key' => $key],
            ['value' => $value],
        );

        Cache::forget("settings:{$companyId}:".($branchId ?? 'company'));

        return $setting;
    }
}
