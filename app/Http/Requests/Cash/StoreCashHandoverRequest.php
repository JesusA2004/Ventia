<?php

namespace App\Http\Requests\Cash;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('cash_session'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'denominations' => ['required', 'array', 'min:1'],
            'denominations.*.denomination' => ['required', 'numeric', 'min:0.01', Rule::in(config('pos.denominations'))],
            'denominations.*.quantity' => ['required', 'integer', 'min:0'],
            'cashier_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
