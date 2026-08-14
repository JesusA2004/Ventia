<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StorePaymentMethodRequest;
use App\Http\Requests\Sales\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\SalePayment;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->authorizeResource(PaymentMethod::class, 'payment_method');
    }

    public function index(Request $request): Response
    {
        $paymentMethods = PaymentMethod::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/PaymentMethods/Index', [
            'paymentMethods' => PaymentMethodResource::collection($paymentMethods),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/PaymentMethods/Create');
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $paymentMethod = PaymentMethod::create($request->validated());

        $this->audit->log('payment-methods', 'created', "Creó el método de pago «{$paymentMethod->name}».", $paymentMethod);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Método de pago creado correctamente.']);

        return to_route('payment-methods.index');
    }

    public function edit(PaymentMethod $paymentMethod): Response
    {
        return Inertia::render('Sales/PaymentMethods/Edit', [
            'paymentMethod' => PaymentMethodResource::make($paymentMethod),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $before = $paymentMethod->only(['name', 'status', 'sort_order']);
        $paymentMethod->update($request->validated());

        $this->audit->log('payment-methods', 'updated', "Actualizó el método de pago «{$paymentMethod->name}».", $paymentMethod, $before, $paymentMethod->only(['name', 'status', 'sort_order']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Método de pago actualizado correctamente.']);

        return to_route('payment-methods.index');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        if (SalePayment::where('payment_method_id', $paymentMethod->id)->exists()) {
            throw ValidationException::withMessages([
                'payment_method' => 'No se puede eliminar: el método de pago tiene ventas registradas. Desactívalo en su lugar.',
            ]);
        }

        $name = $paymentMethod->name;
        $paymentMethod->delete();

        $this->audit->log('payment-methods', 'deleted', "Eliminó el método de pago «{$name}».", $paymentMethod);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Método de pago eliminado correctamente.']);

        return to_route('payment-methods.index');
    }

    /**
     * Lightweight lookup for the POS checkout dialog.
     */
    public function options(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('pos.access') || $request->user()->can('payment-methods.manage'), 403);

        $methods = PaymentMethod::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => PaymentMethodResource::collection($methods)]);
    }
}
