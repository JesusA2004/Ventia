<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentMethod */
class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'requires_reference' => $this->requires_reference,
            'opens_cash_drawer' => $this->opens_cash_drawer,
            'affects_cash' => $this->affects_cash,
            'allows_change' => $this->allows_change,
            'sort_order' => $this->sort_order,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}
