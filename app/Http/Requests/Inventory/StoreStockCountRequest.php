<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\StockCount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockCountRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', StockCount::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'notes' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'products.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('company_id', $companyId)],
            'products.*.product_lot_id' => ['nullable', Rule::exists('product_lots', 'id')->where('company_id', $companyId)],
        ];
    }
}
