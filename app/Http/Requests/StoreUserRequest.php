<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'is_active' => ['boolean'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'branch_ids' => ['array'],
            'branch_ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where('company_id', $this->activeCompanyId()),
            ],
        ];
    }
}
