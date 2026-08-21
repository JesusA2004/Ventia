<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\AuditActionLabels;
use App\Support\PaginatedResource;
use App\Support\PermissionLabels;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Superadministrator-only: not gated behind a Spatie permission on purpose
 * (audit.view is granted to a few company-facing roles in the seeder for a
 * different, future purpose) — every action here checks isSuperAdmin()
 * directly, in the controller, so a guessed URL 403s regardless of what the
 * frontend menu shows.
 */
class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, \Closure $next) {
            abort_unless($request->user()?->isSuperAdmin(), 403, 'Solo un Superadministrador puede consultar la auditoría.');

            return $next($request);
        });
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $query = AuditLog::query()
            ->with(['company:id,name', 'branch:id,name'])
            ->when($request->date('date_from'), fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date('date_to'), fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->integer('company_id'), fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->string('module')->toString(), fn ($q, $module) => $q->where('module', $module))
            ->when($request->string('action')->toString(), fn ($q, $action) => $q->where('action', $action))
            ->when($request->string('entity_type')->toString(), fn ($q, $type) => $q->where('entity_type', $type))
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('description', 'like', "%{$search}%")
                ->orWhere('user_name', 'like', "%{$search}%")
            ));

        $total = (clone $query)->count();

        $logs = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Audit/Index', [
            'logs' => PaginatedResource::make($logs, AuditLogResource::class),
            'total' => $total,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'user_id', 'company_id', 'branch_id', 'module', 'action', 'entity_type']),
            'filterOptions' => [
                'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
                'branches' => Branch::query()->withoutGlobalScopes()->orderBy('name')->get(['id', 'name', 'company_id']),
                'users' => User::query()->withoutGlobalScopes()->orderBy('name')->get(['id', 'name']),
                'modules' => collect(AuditLog::query()->distinct()->orderBy('module')->pluck('module'))
                    ->map(fn (string $module) => ['value' => $module, 'label' => PermissionLabels::group($module)])
                    ->values(),
                'actions' => AuditActionLabels::options(AuditLog::query()->distinct()->pluck('action')),
            ],
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $auditLog->load(['company:id,name', 'branch:id,name']);

        return Inertia::render('Audit/Show', [
            'log' => AuditLogResource::make($auditLog),
        ]);
    }
}
