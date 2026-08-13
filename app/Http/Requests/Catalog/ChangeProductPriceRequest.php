<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeProductPriceRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('editPrice', $this->route('product'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        /** @var Product $product */
        $product = $this->route('product');

        return [
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('company_id', $companyId)->where('product_id', $product->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'price_list_id' => ['nullable', Rule::exists('price_lists', 'id')->where('company_id', $companyId)],
        ];
    }
}
