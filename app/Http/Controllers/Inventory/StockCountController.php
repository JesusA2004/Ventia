<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\ApplyStockCountAction;
use App\Actions\Inventory\CancelStockCountAction;
use App\Actions\Inventory\CompleteStockCountAction;
use App\Actions\Inventory\StartStockCountAction;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Controllers\Concerns\GuardsBranchAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CompleteStockCountRequest;
use App\Http\Requests\Inventory\StoreStockCountRequest;
use App\Http\Resources\StockCountResource;
use App\Http\Resources\WarehouseResource;
use App\Models\StockCount;
use App\Models\Warehouse;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockCountController extends Controller
{
    use GuardsBranchAccess;

    public function __construct(
        private readonly StartStockCountAction $startCount,
        private readonly CompleteStockCountAction $completeCount,
        private readonly ApplyStockCountAction $applyCount,
        private readonly CancelStockCountAction $cancelCount,
    ) {
        $this->authorizeResource(StockCount::class, 'count');
    }

    public function index(Request $request): Response
    {
        $counts = StockCount::query()
            ->with('warehouse:id,name')
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventory/Counts/Index', [
            'counts' => PaginatedResource::make($counts, StockCountResource::class),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Counts/Create', [
            'warehouseOptions' => WarehouseResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreStockCountRequest $request): RedirectResponse
    {
        $warehouse = Warehouse::query()->whereKey($request->validated('warehouse_id'))->firstOrFail();

        $this->assertBranchAccessible($request->user(), $warehouse->branch_id);

        $count = $this->startCount->execute($warehouse, $request->validated('products'), $request->validated('notes'), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Conteo {$count->folio} iniciado. Existencia esperada congelada."]);

        return to_route('inventory.counts.show', $count);
    }

    public function show(StockCount $count): Response
    {
        return Inertia::render('Inventory/Counts/Show', [
            'count' => StockCountResource::make($count->load([
                'items.product:id,name,sku',
                'items.variant:id,sku',
                'warehouse:id,name',
                'startedByUser:id,name',
                'completedByUser:id,name',
                'appliedByUser:id,name',
            ])),
        ]);
    }

    public function complete(CompleteStockCountRequest $request, StockCount $count): RedirectResponse
    {
        $this->authorize('manage', $count);

        return $this->handleTransition(
            fn () => $this->completeCount->execute($count, $request->validated('counted'), $request->user()),
            $count,
            'Conteo completado. Revisa las diferencias antes de aplicarlo.',
        );
    }

    public function apply(StockCount $count): RedirectResponse
    {
        $this->authorize('manage', $count);

        return $this->handleTransition(
            fn () => $this->applyCount->execute($count, request()->user()),
            $count,
            'Conteo aplicado: se generaron los ajustes de inventario correspondientes.',
        );
    }

    public function cancel(StockCount $count): RedirectResponse
    {
        $this->authorize('manage', $count);

        return $this->handleTransition(fn () => $this->cancelCount->execute($count), $count, 'Conteo cancelado.');
    }

    private function handleTransition(\Closure $action, StockCount $count, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidStateTransitionException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $successMessage]);

        return to_route('inventory.counts.show', $count);
    }
}
