<?php

namespace App\Http\Requests\Catalog;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeProductCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editCost', $this->route('product'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('company_id', $this->user()->company_id)->where('product_id', $product->id),
            ],
            'cost' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
