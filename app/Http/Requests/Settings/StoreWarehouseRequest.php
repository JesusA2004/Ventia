<?php

namespace App\Http\Requests\Settings;

use App\Enums\Status;
use App\Enums\WarehouseType;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Warehouse;
use App\Support\SequentialCodeGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', Warehouse::class);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('code') && $this->filled('branch_id')) {
            $this->merge(['code' => SequentialCodeGenerator::next(Warehouse::class, 'ALM', ['branch_id' => $this->input('branch_id')])]);
        }
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
                Rule::unique('warehouses', 'code')->where('branch_id', $this->input('branch_id')),
            ],
            'type' => ['required', Rule::enum(WarehouseType::class)],
            'allows_sale' => ['boolean'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
