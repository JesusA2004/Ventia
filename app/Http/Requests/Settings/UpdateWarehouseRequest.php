<?php

namespace App\Http\Requests\Settings;

use App\Enums\Status;
use App\Enums\WarehouseType;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('warehouse'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $this->activeCompanyId()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('warehouses', 'code')
                    ->where('branch_id', $this->input('branch_id'))
                    ->ignore($this->route('warehouse')),
            ],
            'type' => ['required', Rule::enum(WarehouseType::class)],
            'allows_sale' => ['boolean'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
