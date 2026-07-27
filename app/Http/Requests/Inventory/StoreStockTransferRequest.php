<?php

namespace App\Http\Requests\Inventory;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockTransfer::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'origin_warehouse_id' => ['required', 'different:destination_warehouse_id', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'destination_warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('company_id', $companyId)],
            'items.*.product_lot_id' => ['nullable', Rule::exists('product_lots', 'id')->where('company_id', $companyId)],
            'items.*.quantity_requested' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
