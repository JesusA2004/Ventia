<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', Brand::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands', 'name')->where('company_id', $this->activeCompanyId()),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
