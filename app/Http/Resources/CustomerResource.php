<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'customer_type' => $this->customer_type->value,
            'customer_type_label' => $this->customer_type->label(),
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tax_id' => $this->tax_id,
            'phone' => $this->phone,
            'phone_country_code' => $this->phone_country_code,
            'email' => $this->email,
            'address' => $this->address,
            'price_list_id' => $this->price_list_id,
            'credit_limit' => (string) $this->credit_limit,
            'current_balance' => (string) $this->current_balance,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sales_count' => $this->whenCounted('sales'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
