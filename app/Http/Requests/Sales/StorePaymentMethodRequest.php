<?php

namespace App\Http\Requests\Sales;

use App\Enums\PaymentMethodType;
use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', PaymentMethod::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', Rule::unique('payment_methods', 'code')->where('company_id', $companyId)],
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'requires_reference' => ['boolean'],
            'opens_cash_drawer' => ['boolean'],
            'affects_cash' => ['boolean'],
            'allows_change' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
