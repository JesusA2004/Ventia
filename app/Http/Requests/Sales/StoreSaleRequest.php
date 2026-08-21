<?php

namespace App\Http\Requests\Sales;

use App\Http\Requests\Concerns\ResolvesActiveCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('sales.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'register_id' => ['required', Rule::exists('registers', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'cash_session_id' => ['nullable', Rule::exists('cash_sessions', 'id')->where('company_id', $companyId)],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'seller_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'checkout_uuid' => ['required', 'uuid'],
            'notes' => ['nullable', 'string'],

            'general_discount' => ['nullable', 'array'],
            'general_discount.type' => ['required_with:general_discount', Rule::in(['percentage', 'fixed'])],
            'general_discount.value' => ['required_with:general_discount', 'numeric', 'min:0'],

            // Existence/eligibility (active, in range, scope, usage limits)
            // is re-validated server-side in PromotionEligibilityService —
            // this rule only bounds the input's shape.
            'coupon_code' => ['nullable', 'string', 'max:40'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],

            // "At least one payment" isn't enforced here: whether that's
            // actually required depends on the sale's total, which isn't
            // known until CreateSaleAction resolves pricing/discounts — a
            // promotion/coupon can legitimately bring it to zero. See
            // SalePaymentValidatorService, which has that context.
            'payments' => ['present', 'array'],
            'payments.*.payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('company_id', $companyId)],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'payments.*.authorization_number' => ['nullable', 'string', 'max:255'],
            'payments.*.card_last_four' => ['nullable', 'string', 'max:4'],
            'payments.*.bank' => ['nullable', 'string', 'max:255'],
            'payments.*.terminal' => ['nullable', 'string', 'max:255'],
        ];
    }
}
