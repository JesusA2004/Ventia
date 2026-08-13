<?php

namespace App\Http\Requests\Cash;

use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\CashSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenCashSessionRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('open', CashSession::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();

        return [
            'register_id' => ['required', Rule::exists('registers', 'id')->where('company_id', $companyId)],
            'opening_amount' => ['required', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string'],
        ];
    }
}
