<?php

namespace App\Http\Requests\Sales;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sales.return');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Sale $sale */
        $sale = $this->route('sale');

        return [
            'reason' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', Rule::exists('sale_items', 'id')->where('sale_id', $sale->id)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.restock' => ['nullable', 'boolean'],
        ];
    }
}
