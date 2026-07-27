<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory.adjust');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('company_id', $companyId)->where('product_id', $this->input('product_id')),
            ],
            'product_lot_id' => [
                'nullable',
                Rule::exists('product_lots', 'id')->where('company_id', $companyId)->where('product_id', $this->input('product_id')),
            ],
            'movement_type' => ['required', Rule::enum(InventoryMovementType::class)],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
