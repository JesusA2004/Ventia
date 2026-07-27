<?php

namespace App\Http\Controllers\Pos;

use App\Actions\Sales\CancelSaleAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Actions\Sales\ProcessSaleReturnAction;
use App\Actions\Sales\ResumeSaleAction;
use App\Actions\Sales\SuspendSaleAction;
use App\Enums\SaleStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Controllers\Concerns\GuardsBranchAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CancelSaleRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\StoreSaleReturnRequest;
use App\Http\Requests\Sales\SuspendSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Support\PaginatedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class SaleController extends Controller
{
    use GuardsBranchAccess;

    public function __construct(
        private readonly CompleteSaleAction $completeSale,
        private readonly SuspendSaleAction $suspendSale,
        private readonly ResumeSaleAction $resumeSale,
        private readonly CancelSaleAction $cancelSale,
        private readonly ProcessSaleReturnAction $processReturn,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);

        $sales = Sale::query()
            ->with(['customer:id,name', 'cashier:id,name', 'register:id,name', 'payments.paymentMethod:id,name'])
            ->when(! $request->user()->can('cash.view'), fn ($q) => $q->where('cashier_id', $request->user()->id))
            ->when($request->string('folio')->toString(), fn ($q, $folio) => $q->where('folio', 'like', "%{$folio}%"))
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->integer('cashier_id'), fn ($q, $id) => $q->where('cashier_id', $id))
            ->when($request->integer('register_id'), fn ($q, $id) => $q->where('register_id', $id))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->date('date_from'), fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date('date_to'), fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => PaginatedResource::make($sales, SaleResource::class),
            'filters' => $request->only(['folio', 'customer_id', 'cashier_id', 'register_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function show(Sale $sale): Response
    {
        $this->authorize('view', $sale);

        $sale->load([
            'items.lot:id,lot_number',
            'payments.paymentMethod',
            'customer', 'cashier:id,name', 'seller:id,name', 'register:id,name',
            'returns.items',
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => SaleResource::make($sale),
        ]);
    }

    public function ticket(Sale $sale): Response
    {
        $this->authorize('reprintTicket', $sale);

        $sale->load(['items', 'payments.paymentMethod', 'customer', 'cashier:id,name', 'register.branch.company']);

        return Inertia::render('Sales/Ticket', [
            'sale' => SaleResource::make($sale),
            'company' => $sale->register->branch->company,
            'branch' => $sale->register->branch,
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $register = CashRegister::query()->whereKey($request->validated('register_id'))->firstOrFail();
        $warehouse = Warehouse::query()->whereKey($request->validated('warehouse_id'))->firstOrFail();

        $this->assertBranchAccessible($request->user(), $register->branch_id);

        if ($warehouse->branch_id !== $register->branch_id) {
            throw ValidationException::withMessages(['warehouse_id' => 'El almacén debe pertenecer a la misma sucursal que la caja.']);
        }

        try {
            $sale = $this->completeSale->execute([
                'branch_id' => $register->branch_id,
                'warehouse_id' => $warehouse->id,
                'register_id' => $register->id,
                'cash_session_id' => $request->validated('cash_session_id'),
                'customer_id' => $request->validated('customer_id'),
                'seller_id' => $request->validated('seller_id'),
                'notes' => $request->validated('notes'),
                'checkout_uuid' => $request->validated('checkout_uuid'),
                'general_discount' => $request->validated('general_discount'),
                'items' => $request->validated('items'),
            ], $request->validated('payments'), $request->user());
        } catch (InsufficientStockException|InvalidArgumentException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        return response()->json(['data' => SaleResource::make($sale->load('items', 'payments.paymentMethod'))], 201);
    }

    public function suspend(SuspendSaleRequest $request): JsonResponse
    {
        $register = CashRegister::query()->whereKey($request->validated('register_id'))->firstOrFail();
        $warehouse = Warehouse::query()->whereKey($request->validated('warehouse_id'))->firstOrFail();

        $this->assertBranchAccessible($request->user(), $register->branch_id);

        try {
            $sale = $this->suspendSale->execute([
                'branch_id' => $register->branch_id,
                'warehouse_id' => $warehouse->id,
                'register_id' => $register->id,
                'cash_session_id' => $request->validated('cash_session_id'),
                'customer_id' => $request->validated('customer_id'),
                'notes' => $request->validated('notes'),
                'items' => $request->validated('items'),
            ], $request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        return response()->json(['data' => SaleResource::make($sale->load('items'))], 201);
    }

    public function suspended(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('sales.suspend'), 403);

        $sales = Sale::query()
            ->where('status', SaleStatus::Suspended)
            ->where('register_id', $request->integer('register_id'))
            ->with(['customer:id,name', 'items'])
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => SaleResource::collection($sales)]);
    }

    public function resume(Sale $sale, Request $request): JsonResponse
    {
        abort_unless($request->user()->can('sales.suspend'), 403);

        try {
            $fresh = $this->resumeSale->execute($sale, $request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['sale' => $e->getMessage()]);
        }

        return response()->json(['data' => SaleResource::make($fresh->load('items'))]);
    }

    public function destroySuspended(Sale $sale, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('sales.suspend'), 403);

        if ($sale->status !== SaleStatus::Suspended) {
            throw ValidationException::withMessages(['sale' => 'Solo se pueden eliminar ventas suspendidas.']);
        }

        $sale->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta suspendida eliminada.']);

        return back();
    }

    public function cancel(CancelSaleRequest $request, Sale $sale): RedirectResponse
    {
        $this->authorize('cancel', $sale);

        try {
            $this->cancelSale->execute($sale, $request->validated('reason'), $request->user());
        } catch (InvalidStateTransitionException $e) {
            throw ValidationException::withMessages(['sale' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta cancelada correctamente.']);

        return to_route('sales.show', $sale);
    }

    public function storeReturn(StoreSaleReturnRequest $request, Sale $sale): RedirectResponse
    {
        $this->authorize('return', $sale);

        try {
            $this->processReturn->execute($sale, $request->validated('items'), $request->validated('reason'), $request->user());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Devolución registrada correctamente.']);

        return to_route('sales.show', $sale);
    }
}
