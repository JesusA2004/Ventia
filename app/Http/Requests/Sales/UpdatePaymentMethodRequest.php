<?php

namespace App\Http\Requests\Sales;

use App\Enums\PaymentMethodType;
use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment_method'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('payment_methods', 'code')->where('company_id', $companyId)->ignore($this->route('payment_method')),
            ],
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
