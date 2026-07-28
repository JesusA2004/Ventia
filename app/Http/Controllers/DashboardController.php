<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchResource;
use App\Http\Resources\CashRegisterResource;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const PRESETS = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'custom'];

    public function __construct(private readonly DashboardMetricsService $metrics) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        $user = $request->user();
        $preset = in_array($request->string('preset')->toString(), self::PRESETS, true)
            ? $request->string('preset')->toString()
            : 'today';

        [$from, $to] = $this->resolveRange($preset, $request->date('date_from'), $request->date('date_to'));

        $filters = [
            'branch_id' => $request->integer('branch_id') ?: null,
            'register_id' => $request->integer('register_id') ?: null,
            'cashier_id' => $request->integer('cashier_id') ?: null,
        ];

        $granularity = in_array($request->string('granularity')->toString(), ['hour', 'day', 'week'], true)
            ? $request->string('granularity')->toString()
            : 'day';

        return Inertia::render('Dashboard', [
            'metrics' => $this->metrics->build($user, $from, $to, $filters, $granularity),
            'filters' => [
                'preset' => $preset,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'granularity' => $granularity,
                ...$filters,
            ],
            'branchOptions' => BranchResource::collection(Branch::query()->orderBy('name')->get()),
            'registerOptions' => CashRegisterResource::collection(CashRegister::query()->orderBy('name')->get()),
            'cashierOptions' => User::query()
                ->when($user->company_id, fn ($q, $id) => $q->where('company_id', $id))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveRange(string $preset, ?Carbon $customFrom, ?Carbon $customTo): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()->endOfDay()],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfDay()],
            'last_week' => [$today->copy()->subWeek()->startOfWeek(), $today->copy()->subWeek()->endOfWeek()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [
                ($customFrom ?? $today)->copy()->startOfDay(),
                ($customTo ?? $today)->copy()->endOfDay(),
            ],
            default => [$today->copy(), $today->copy()->endOfDay()],
        };
    }
}
