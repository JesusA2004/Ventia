<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Models\ProductAttribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductAttribute::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('product_attributes', 'name')->where('company_id', $this->user()->company_id),
            ],
            'status' => ['required', Rule::enum(Status::class)],
            'values' => ['array'],
            'values.*.value' => ['required', 'string', 'max:255'],
        ];
    }
}
