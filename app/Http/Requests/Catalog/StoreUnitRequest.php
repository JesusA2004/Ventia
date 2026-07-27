<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Unit::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:10'],
            'type' => ['required', Rule::enum(UnitType::class)],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'allows_fraction' => ['boolean'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0'],
            'base_unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->orWhereNull('company_id')),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
