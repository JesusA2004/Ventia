<?php

namespace App\Http\Controllers\Cash;

use App\Actions\Cash\CloseCashSessionAction;
use App\Actions\Cash\OpenCashSessionAction;
use App\Actions\Cash\RegisterCashMovementAction;
use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Exceptions\CashSessionConflictException;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Controllers\Concerns\GuardsBranchAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\CloseCashSessionRequest;
use App\Http\Requests\Cash\OpenCashSessionRequest;
use App\Http\Requests\Cash\RegisterCashMovementRequest;
use App\Http\Resources\CashMovementResource;
use App\Http\Resources\CashSessionResource;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Services\Audit\AuditLogger;
use App\Services\Cash\CashSessionSummaryService;
use App\Services\SettingsService;
use App\Support\PaginatedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CashSessionController extends Controller
{
    use GuardsBranchAccess;

    public function __construct(
        private readonly OpenCashSessionAction $openSession,
        private readonly CloseCashSessionAction $closeSession,
        private readonly RegisterCashMovementAction $registerMovement,
        private readonly CashSessionSummaryService $summary,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashSession::class);

        $sessions = CashSession::query()
            ->with(['register:id,name', 'user:id,name'])
            ->when(! $request->user()->can('cash.view'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Cash/Sessions/Index', [
            'sessions' => PaginatedResource::make($sessions, CashSessionResource::class),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('open', CashSession::class);

        $user = Auth::user();

        $registers = CashRegister::query()
            ->with('branch:id,name')
            ->accessibleBy($user)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Cash/Open', [
            'registerOptions' => $registers->map(fn (CashRegister $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'branch_id' => $r->branch_id,
                'branch_name' => $r->branch->name,
            ]),
        ]);
    }

    public function store(OpenCashSessionRequest $request): RedirectResponse
    {
        $register = CashRegister::query()->whereKey($request->validated('register_id'))->firstOrFail();

        $this->assertBranchAccessible($request->user(), $register->branch_id);

        try {
            $session = $this->openSession->execute(
                $register,
                $request->user(),
                (string) $request->validated('opening_amount'),
                $request->validated('opening_notes'),
            );
        } catch (CashSessionConflictException $e) {
            throw ValidationException::withMessages(['register_id' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caja abierta correctamente. Ya puedes comenzar a vender.']);

        return to_route('pos.index', ['cash_session' => $session->id]);
    }

    /**
     * Used by the POS shell on load to know whether the current user already
     * has an open session, and to redirect to /cash/open otherwise.
     */
    public function current(Request $request): JsonResponse
    {
        $session = CashSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', CashSessionStatus::Open)
            ->with(['register:id,name'])
            ->first();

        return response()->json(['data' => $session ? CashSessionResource::make($session) : null]);
    }

    public function show(CashSession $cashSession): Response
    {
        $this->authorize('view', $cashSession);

        $cashSession->load(['register:id,name', 'user:id,name']);
        $movements = $cashSession->movements()->with('user:id,name')->orderByDesc('id')->get();

        return Inertia::render('Cash/Sessions/Show', [
            'session' => CashSessionResource::make($cashSession),
            'movements' => CashMovementResource::collection($movements),
            'summary' => $cashSession->status === CashSessionStatus::Open ? $this->summary->build($cashSession) : null,
        ]);
    }

    public function showCloseForm(CashSession $cashSession, SettingsService $settings): Response
    {
        $this->authorize('close', $cashSession);

        if ((bool) ($settings->get($cashSession->company_id, 'cash_handover_required') ?? false)) {
            return Inertia::render('Cash/RequestHandover', [
                'session' => CashSessionResource::make($cashSession->load(['register:id,name', 'user:id,name'])),
                'summary' => $this->summary->build($cashSession),
                'denominationOptions' => config('pos.denominations'),
            ]);
        }

        return Inertia::render('Cash/Close', [
            'session' => CashSessionResource::make($cashSession->load(['register:id,name', 'user:id,name'])),
            'summary' => $this->summary->build($cashSession),
        ]);
    }

    public function close(CloseCashSessionRequest $request, CashSession $cashSession, SettingsService $settings): RedirectResponse
    {
        abort_if((bool) ($settings->get($cashSession->company_id, 'cash_handover_required') ?? false), 403, 'Esta empresa requiere entrega de caja supervisada. Usa el flujo de entrega de caja.');

        try {
            $session = $this->closeSession->execute(
                $cashSession,
                (string) $request->validated('counted_cash'),
                $request->validated('closing_notes'),
                $request->user(),
            );
        } catch (InvalidStateTransitionException $e) {
            throw ValidationException::withMessages(['counted_cash' => $e->getMessage()]);
        }

        $registerName = $session->register?->name ?? $cashSession->register_id;
        $this->audit->log(
            'cash', 'closed',
            "Cerró la caja «{$registerName}»".($session->difference !== null && (float) $session->difference !== 0.0 ? " con una diferencia de {$session->difference}." : '.'),
            $session,
            newValues: ['counted_cash' => (string) $session->counted_cash, 'difference' => (string) $session->difference],
            branchId: $session->branch_id,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Caja cerrada correctamente.']);

        return to_route('cash.sessions.show', $session);
    }

    public function storeMovement(RegisterCashMovementRequest $request, CashSession $cashSession): RedirectResponse
    {
        $type = match ($request->validated('type')) {
            'deposit' => CashMovementType::Deposit,
            'withdrawal' => CashMovementType::Withdrawal,
            'expense' => CashMovementType::Expense,
            default => throw new InvalidArgumentException('Tipo de movimiento inválido.'),
        };

        $permission = match ($type) {
            CashMovementType::Deposit => 'cash.register-deposit',
            CashMovementType::Withdrawal => 'cash.register-withdrawal',
            CashMovementType::Expense => 'cash.register-expense',
        };

        abort_unless($request->user()->can($permission), 403);

        try {
            $this->registerMovement->execute(
                $cashSession,
                $type,
                (string) $request->validated('amount'),
                $request->validated('reason'),
                $request->user(),
                $request->validated('notes'),
            );
        } catch (InvalidStateTransitionException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Movimiento de caja registrado correctamente.']);

        return back();
    }
}
