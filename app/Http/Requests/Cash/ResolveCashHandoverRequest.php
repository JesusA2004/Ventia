<?php

namespace App\Http\Requests\Cash;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveCashHandoverRequest extends FormRequest
{
    /**
     * The acting session only needs to be able to *see* this handover (the
     * cashier who requested it, or a viewer/approver in the same company) —
     * the actual approve/reject/recount authorization is enforced inside
     * CashHandoverController::resolve() via the supervisor_email/password
     * lookup, precisely so a different, authorized user can validate the
     * decision without the cashier's own session being logged out.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('cash_handover'));
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
