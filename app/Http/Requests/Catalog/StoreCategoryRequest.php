<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('company_id', $companyId),
            ],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')
                    ->where('company_id', $companyId)
                    ->where('parent_id', $this->input('parent_id')),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
