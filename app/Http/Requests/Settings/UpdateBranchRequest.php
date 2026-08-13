<?php

namespace App\Http\Requests\Settings;

use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('branch'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('branches', 'code')
                    ->where('company_id', $this->activeCompanyId())
                    ->ignore($this->route('branch')),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', $this->activeCompanyId())],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
