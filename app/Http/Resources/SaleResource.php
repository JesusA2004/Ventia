<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'register_id' => $this->register_id,
            'register_name' => $this->whenLoaded('register', fn () => $this->register->name),
            'cash_session_id' => $this->cash_session_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer->name),
            'cashier_id' => $this->cashier_id,
            'cashier_name' => $this->whenLoaded('cashier', fn () => $this->cashier->name),
            'subtotal' => (string) $this->subtotal,
            'discount_total' => (string) $this->discount_total,
            'tax_total' => (string) $this->tax_total,
            'total' => (string) $this->total,
            'cost_total' => $this->when($request->user()?->can('products.view-costs') ?? false, (string) $this->cost_total),
            'profit_total' => $this->when($request->user()?->can('products.view-costs') ?? false, (string) $this->profit_total),
            'amount_received' => (string) $this->amount_received,
            'change_amount' => (string) $this->change_amount,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'payments' => SalePaymentResource::collection($this->whenLoaded('payments')),
            'payment_method_names' => $this->whenLoaded('payments', fn () => $this->payments
                ->pluck('paymentMethod.name')
                ->unique()
                ->values()),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
