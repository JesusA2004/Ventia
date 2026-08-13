<?php

namespace App\Support;

/**
 * Suggests the next "PREFIX-001" style code for entities that currently make
 * users invent one by hand (branches, warehouses, registers). Scans existing
 * codes under the given scope (e.g. company_id or branch_id) rather than
 * keeping a counter, so it stays correct even if codes were deleted or
 * created out of order. This is a suggestion only — the real safety net
 * against duplicates/races is still the Rule::unique in each FormRequest;
 * a collision here just means the user retries with the next number.
 */
final class SequentialCodeGenerator
{
    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $scope  Column => value constraints (e.g. ['company_id' => 3])
     */
    public static function next(string $modelClass, string $prefix, array $scope = [], string $column = 'code'): string
    {
        $query = $modelClass::query()->withoutGlobalScopes();

        foreach ($scope as $key => $value) {
            $query->where($key, $value);
        }

        $max = $query
            ->where($column, 'like', "{$prefix}-%")
            ->pluck($column)
            ->map(fn ($code) => (int) substr((string) $code, strlen($prefix) + 1))
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefix, $max + 1);
    }
}
