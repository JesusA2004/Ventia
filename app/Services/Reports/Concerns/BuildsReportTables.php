<?php

namespace App\Services\Reports\Concerns;

/**
 * Shared row/table shaping used by every report service, so the screen,
 * CSV, PDF and Excel exports all consume the exact same
 * {title, columns, rows} shape regardless of which tab built it.
 */
trait BuildsReportTables
{
    /**
     * @param  iterable<mixed>  $rows
     * @param  list<string>  $columns
     * @param  \Closure(mixed): list<mixed>  $mapRow
     * @return array{title: string, columns: list<string>, rows: list<list<mixed>>}
     */
    private function tableFrom(string $title, array $columns, iterable $rows, \Closure $mapRow): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => array_values(collect($rows)->map($mapRow)->all()),
        ];
    }
}
