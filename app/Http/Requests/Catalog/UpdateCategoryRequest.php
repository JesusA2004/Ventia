<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
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
                    ->where('parent_id', $this->input('parent_id'))
                    ->ignore($this->route('category')),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');

            if ($parentId === null) {
                return;
            }

            /** @var Category $category */
            $category = $this->route('category');

            if ((int) $parentId === $category->id) {
                $validator->errors()->add('parent_id', 'Una categoría no puede ser su propio padre.');

                return;
            }

            if ($this->isDescendant($category, (int) $parentId)) {
                $validator->errors()->add('parent_id', 'No se puede asignar una subcategoría como padre (crearía un ciclo).');
            }
        });
    }

    private function isDescendant(Category $category, int $candidateId): bool
    {
        foreach ($category->children()->pluck('id') as $childId) {
            if ($childId === $candidateId) {
                return true;
            }

            $child = Category::query()->whereKey($childId)->first();

            if ($child && $this->isDescendant($child, $candidateId)) {
                return true;
            }
        }

        return false;
    }
}
