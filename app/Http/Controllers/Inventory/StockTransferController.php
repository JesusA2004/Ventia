<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\ApproveTransferAction;
use App\Actions\Inventory\CancelTransferAction;
use App\Actions\Inventory\CreateTransferAction;
use App\Actions\Inventory\ReceiveTransferAction;
use App\Actions\Inventory\ShipTransferAction;
use App\Actions\Inventory\SubmitTransferAction;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Controllers\Concerns\GuardsBranchAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReceiveStockTransferRequest;
use App\Http\Requests\Inventory\StoreStockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Http\Resources\WarehouseResource;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    use GuardsBranchAccess;

    public function __construct(
        private readonly CreateTransferAction $createTransfer,
        private readonly SubmitTransferAction $submitTransfer,
        private readonly ApproveTransferAction $approveTransfer,
        private readonly ShipTransferAction $shipTransfer,
        private readonly ReceiveTransferAction $receiveTransfer,
        private readonly CancelTransferAction $cancelTransfer,
    ) {
        $this->authorizeResource(StockTransfer::class, 'transfer');
    }

    public function index(Request $request): Response
    {
        $transfers = StockTransfer::query()
            ->with(['originWarehouse:id,name', 'destinationWarehouse:id,name'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventory/Transfers/Index', [
            'transfers' => PaginatedResource::make($transfers, StockTransferResource::class),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Transfers/Create', [
            'warehouseOptions' => WarehouseResource::collection(Warehouse::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $origin = Warehouse::query()->whereKey($request->validated('origin_warehouse_id'))->firstOrFail();
        $destination = Warehouse::query()->whereKey($request->validated('destination_warehouse_id'))->firstOrFail();

        $this->assertBranchAccessible($request->user(), $origin->branch_id);
        $this->assertBranchAccessible($request->user(), $destination->branch_id);

        $transfer = $this->createTransfer->execute(
            $origin,
            $destination,
            $request->validated('items'),
            $request->validated('notes'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Transferencia {$transfer->folio} creada como borrador."]);

        return to_route('inventory.transfers.show', $transfer);
    }

    public function show(StockTransfer $transfer): Response
    {
        return Inertia::render('Inventory/Transfers/Show', [
            'transfer' => StockTransferResource::make($transfer->load([
                'items.product:id,name,sku',
                'items.variant:id,sku',
                'originWarehouse:id,name',
                'destinationWarehouse:id,name',
                'requestedByUser:id,name',
                'approvedByUser:id,name',
                'shippedByUser:id,name',
                'receivedByUser:id,name',
            ])),
        ]);
    }

    public function submit(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('manage', $transfer);

        return $this->handleTransition(fn () => $this->submitTransfer->execute($transfer), $transfer, 'Transferencia enviada a aprobación.');
    }

    public function approve(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('manage', $transfer);

        return $this->handleTransition(fn () => $this->approveTransfer->execute($transfer, request()->user()), $transfer, 'Transferencia aprobada.');
    }

    public function ship(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('manage', $transfer);

        return $this->handleTransition(fn () => $this->shipTransfer->execute($transfer, request()->user()), $transfer, 'Transferencia marcada en tránsito.');
    }

    public function receive(ReceiveStockTransferRequest $request, StockTransfer $transfer): RedirectResponse
    {
        $this->assertBranchAccessible($request->user(), $transfer->destinationWarehouse->branch_id);

        return $this->handleTransition(
            fn () => $this->receiveTransfer->execute($transfer, $request->validated('received'), $request->user()),
            $transfer,
            'Recepción registrada correctamente.',
        );
    }

    public function cancel(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('manage', $transfer);

        return $this->handleTransition(fn () => $this->cancelTransfer->execute($transfer), $transfer, 'Transferencia cancelada.');
    }

    private function handleTransition(\Closure $action, StockTransfer $transfer, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidStateTransitionException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $successMessage]);

        return to_route('inventory.transfers.show', $transfer);
    }
}
