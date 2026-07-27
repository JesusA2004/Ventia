<?php

namespace App\Http\Requests\Inventory;

use App\Enums\Status;
use App\Models\ProductLot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductLot::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('company_id', $companyId)->where('product_id', $this->input('product_id')),
            ],
            'lot_number' => [
                'required', 'string', 'max:255',
                Rule::unique('product_lots', 'lot_number')->where('company_id', $companyId)->where('product_id', $this->input('product_id')),
            ],
            'manufacture_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:manufacture_date'],
            'received_at' => ['nullable', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
            'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'warehouse_id' => ['required_with:initial_quantity', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
        ];
    }
}
