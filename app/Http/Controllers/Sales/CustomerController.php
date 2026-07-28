<?php

namespace App\Http\Controllers\Sales;

use App\Enums\CustomerType;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreCustomerRequest;
use App\Http\Requests\Sales\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PriceListResource;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\PriceList;
use App\Support\PaginatedResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('tax_id', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Customers/Index', [
            'customers' => PaginatedResource::make($customers, CustomerResource::class),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Customers/Create', $this->formOptions());
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado correctamente.']);

        return to_route('customers.index');
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Sales/Customers/Edit', [
            'customer' => CustomerResource::make($customer),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado correctamente.']);

        return to_route('customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->customer_type === CustomerType::GeneralPublic) {
            throw ValidationException::withMessages([
                'customer' => 'No se puede eliminar el cliente «Público general».',
            ]);
        }

        if ($customer->sales()->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'No se puede eliminar: el cliente tiene ventas registradas. Desactívalo en su lugar.',
            ]);
        }

        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente eliminado correctamente.']);

        return to_route('customers.index');
    }

    /**
     * Lightweight lookup used by the POS customer picker.
     */
    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('pos.access') || $request->user()->can('customers.view'), 403);

        $search = $request->string('search')->toString();

        $customers = Customer::query()
            ->where('status', 'active')
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('tax_id', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json(CustomerResource::collection($customers));
    }

    public function salesHistory(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $sales = $customer->sales()
            ->with(['cashier:id,name'])
            ->orderByDesc('id')
            ->paginate(15);

        $countedSales = $customer->sales()
            ->whereIn('status', [SaleStatus::Completed, SaleStatus::PartiallyReturned]);

        $totalPurchased = (float) (clone $countedSales)->sum('total');
        $purchaseCount = (clone $countedSales)->count();
        $lastPurchaseAt = (clone $countedSales)->max('completed_at');

        return Inertia::render('Sales/Customers/SalesHistory', [
            'customer' => CustomerResource::make($customer),
            'sales' => PaginatedResource::make($sales, SaleResource::class),
            'stats' => [
                'total_purchased' => number_format($totalPurchased, 2, '.', ''),
                'last_purchase_at' => $lastPurchaseAt,
                'average_ticket' => number_format($purchaseCount > 0 ? $totalPurchased / $purchaseCount : 0, 2, '.', ''),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'priceListOptions' => PriceListResource::collection(PriceList::query()->orderBy('name')->get()),
        ];
    }
}
