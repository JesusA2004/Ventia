<?php

namespace App\Http\Requests\Promotions;

use App\Enums\DiscountType;
use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', Promotion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(DiscountType::class)],
            'value' => [
                'required', 'numeric', 'min:0.01',
                Rule::when($this->input('type') === DiscountType::Percentage->value, ['max:100']),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::enum(Status::class)],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'priority' => ['integer', 'min:0'],
            'combinable' => ['boolean'],
            'notes' => ['nullable', 'string'],

            'branch_ids' => ['array'],
            'branch_ids.*' => [Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'product_ids' => ['array'],
            'product_ids.*' => [Rule::exists('products', 'id')->where('company_id', $companyId)],
            'category_ids' => ['array'],
            'category_ids.*' => [Rule::exists('categories', 'id')->where('company_id', $companyId)],
        ];
    }
}
