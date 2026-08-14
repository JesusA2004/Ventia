<?php

namespace App\Services\Reports;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use App\Services\Reports\Concerns\BuildsReportTables;
use Carbon\CarbonInterface;

/**
 * "Caja" tab: sessions with cash-count differences, plus movements grouped
 * by type. Both models carry their own company/branch scoping via
 * accessibleBy(), so no manual join scoping is needed here (unlike Sales).
 */
class CashReportService
{
    use BuildsReportTables;

    /**
     * @return array{kpis: list<array{label: string, value: string}>, tables: list<array{title: string, columns: list<string>, rows: list<list<mixed>>}>}
     */
    public function build(User $user, CarbonInterface $from, CarbonInterface $to, ?int $branchId): array
    {
        $sessionsWithDifferences = CashSession::query()->accessibleBy($user)
            ->with(['register:id,name', 'user:id,name'])
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('difference')
            ->where('difference', '!=', 0)
            ->orderByDesc('closed_at')
            ->get();

        $movementsByType = CashMovement::query()->accessibleBy($user)
            ->when($branchId, fn ($q, $id) => $q->where('branch_id', $id))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('type, COUNT(*) as movements, SUM(amount) as total')
            ->groupBy('type')->orderBy('type')->get();

        return [
            'kpis' => [],
            'tables' => [
                $this->tableFrom(
                    'Sesiones de caja con diferencias',
                    ['Caja', 'Cajero', 'Cerrada', 'Esperado', 'Contado', 'Diferencia'],
                    $sessionsWithDifferences,
                    fn ($s) => [
                        $s->register?->name, $s->user?->name, $s->closed_at?->toDateTimeString(),
                        number_format((float) $s->expected_cash, 2), number_format((float) $s->counted_cash, 2), number_format((float) $s->difference, 2),
                    ],
                ),
                $this->tableFrom(
                    'Movimientos de caja por tipo',
                    ['Tipo', 'Movimientos', 'Total'],
                    $movementsByType,
                    fn ($r) => [$r->type->label(), $r->movements, number_format((float) $r->total, 2)],
                ),
            ],
        ];
    }
}
