<?php

namespace App\Http\Controllers\Cash;

use App\Actions\Cash\RequestCashHandoverAction;
use App\Actions\Cash\ResolveCashHandoverAction;
use App\Enums\CashHandoverStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\ResolveCashHandoverRequest;
use App\Http\Requests\Cash\StoreCashHandoverRequest;
use App\Http\Resources\CashHandoverResource;
use App\Models\CashHandover;
use App\Models\CashSession;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CashHandoverController extends Controller
{
    public function __construct(
        private readonly RequestCashHandoverAction $requestHandover,
        private readonly ResolveCashHandoverAction $resolveHandover,
    ) {}

    /**
     * Pending handovers waiting for a supervisor's review.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashHandover::class);

        $handovers = CashHandover::query()
            ->with(['cashier:id,name', 'cashSession.register:id,name'])
            ->where('status', CashHandoverStatus::Pending)
            ->orderBy('requested_at')
            ->paginate(15);

        return Inertia::render('Cash/Handovers/Index', [
            'handovers' => PaginatedResource::make($handovers, CashHandoverResource::class),
        ]);
    }

    public function store(StoreCashHandoverRequest $request, CashSession $cashSession, SettingsService $settings): RedirectResponse
    {
        abort_unless((bool) ($settings->get($cashSession->company_id, 'cash_handover_required') ?? false), 404);

        try {
            $this->requestHandover->execute(
                $cashSession,
                $request->validated('denominations'),
                $request->validated('cashier_notes'),
                $request->user(),
            );
        } catch (InvalidStateTransitionException|\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['denominations' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Entrega de caja enviada. Un supervisor debe revisarla para cerrar tu turno.']);

        return to_route('cash.sessions.show', $cashSession);
    }

    public function show(CashHandover $cashHandover): Response
    {
        $this->authorize('view', $cashHandover);

        return Inertia::render('Cash/Handovers/Show', [
            'handover' => CashHandoverResource::make($cashHandover->load(['cashier:id,name', 'approver:id,name', 'cashSession.register:id,name'])),
        ]);
    }

    public function resolve(ResolveCashHandoverRequest $request, CashHandover $cashHandover, SettingsService $settings): RedirectResponse
    {
        $supervisor = User::query()->where('email', $request->validated('supervisor_email'))->first();

        if ($supervisor === null || ! Hash::check($request->validated('supervisor_password'), $supervisor->password)) {
            throw ValidationException::withMessages(['supervisor_password' => 'Correo o contraseña de supervisor inválidos.']);
        }

        if ($supervisor->company_id !== $cashHandover->company_id || ! $supervisor->can('cash.approve-close')) {
            throw ValidationException::withMessages(['supervisor_email' => 'Ese usuario no tiene permiso para aprobar cierres de caja.']);
        }

        $allowSelfApproval = (bool) ($settings->get($cashHandover->company_id, 'cash_handover_allow_self_approval') ?? false);

        if (! $allowSelfApproval && $supervisor->id === $cashHandover->cashier_id) {
            throw ValidationException::withMessages(['supervisor_email' => 'El mismo cajero no puede autorizar su propia entrega de caja.']);
        }

        try {
            match ((string) $request->validated('decision')) {
                'approve' => $this->resolveHandover->approve($cashHandover, $supervisor, $request->validated('notes')),
                'reject' => $this->resolveHandover->reject($cashHandover, $supervisor, $request->validated('notes')),
                default => $this->resolveHandover->requestRecount($cashHandover, $supervisor, $request->validated('notes')),
            };
        } catch (InvalidStateTransitionException $e) {
            throw ValidationException::withMessages(['decision' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Entrega de caja resuelta correctamente.']);

        return to_route('cash.handovers.index');
    }
}
