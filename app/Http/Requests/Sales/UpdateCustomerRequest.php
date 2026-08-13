<?php

namespace App\Http\Requests\Sales;

use App\Enums\CustomerType;
use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Rules\MexicanRfc;
use App\Rules\PhoneNumber;
use App\Support\PhoneCountryCodes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('customer'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_id' => $this->filled('tax_id') ? strtoupper(trim((string) $this->input('tax_id'))) : null,
            'phone' => $this->filled('phone') ? preg_replace('/\D/', '', (string) $this->input('phone')) : null,
            'phone_country_code' => $this->input('phone_country_code') ?: PhoneCountryCodes::DEFAULT,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();
        $customerType = CustomerType::tryFrom((string) $this->input('customer_type'));

        return [
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'customer_type' => ['required', Rule::enum(CustomerType::class)],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:13', new MexicanRfc($customerType)],
            'phone_country_code' => ['required', Rule::in(PhoneCountryCodes::all())],
            'phone' => ['nullable', 'string', new PhoneNumber($this->string('phone_country_code')->toString())],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'price_list_id' => ['nullable', Rule::exists('price_lists', 'id')->where('company_id', $companyId)],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['tax_id' => 'RFC'];
    }
}
