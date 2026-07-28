<?php

namespace App\Http\Requests\Cash;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveCashHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('resolve', $this->route('cash_handover'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject', 'recount'])],
            'supervisor_email' => ['required', 'email'],
            'supervisor_password' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000', 'required_unless:decision,approve'],
        ];
    }
}
