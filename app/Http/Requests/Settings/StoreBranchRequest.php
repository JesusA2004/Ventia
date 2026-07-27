<?php

namespace App\Http\Requests\Settings;

use App\Enums\Status;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Branch::class);
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
                Rule::unique('branches', 'code')->where('company_id', $this->user()->company_id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
