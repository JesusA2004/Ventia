<?php

namespace App\Http\Requests\Coupons;

use App\Enums\DiscountType;
use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('coupon'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge(['code' => Coupon::normalizeCode($this->input('code'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'code' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('coupons', 'code')->where('company_id', $companyId)->ignore($this->route('coupon')),
            ],
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
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo puede contener letras, números y guiones.',
        ];
    }
}
