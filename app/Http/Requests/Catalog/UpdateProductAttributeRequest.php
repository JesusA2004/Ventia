<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('attribute'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('product_attributes', 'name')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($this->route('attribute')),
            ],
            'status' => ['required', Rule::enum(Status::class)],
            'values' => ['array'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['required', 'string', 'max:255'],
        ];
    }
}
