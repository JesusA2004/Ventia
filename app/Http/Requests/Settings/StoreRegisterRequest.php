<?php

namespace App\Http\Requests\Settings;

use App\Enums\Status;
use App\Models\CashRegister;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CashRegister::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $this->user()->company_id),
            ],
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where('branch_id', $this->input('branch_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('registers', 'code')->where('branch_id', $this->input('branch_id')),
            ],
            'printer_name' => ['nullable', 'string', 'max:255'],
            'has_cash_drawer' => ['boolean'],
            'assigned_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $this->user()->company_id),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
