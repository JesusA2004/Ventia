<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Enums\TaxType;
use App\Models\Tax;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Tax::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('taxes', 'code')->where('company_id', $this->user()->company_id),
            ],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', Rule::enum(TaxType::class)],
            'included_in_price' => ['boolean'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
