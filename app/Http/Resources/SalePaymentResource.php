<?php

namespace App\Http\Resources;

use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalePayment */
class SalePaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_method_id' => $this->payment_method_id,
            'payment_method_name' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod->name),
            'amount' => (string) $this->amount,
            'reference' => $this->reference,
            'authorization_number' => $this->authorization_number,
            'card_last_four' => $this->card_last_four,
            'bank' => $this->bank,
            'terminal' => $this->terminal,
            'status' => $this->status,
            'paid_at' => $this->paid_at->toIso8601String(),
        ];
    }
}
