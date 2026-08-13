<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Enums\UnitType;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUnitRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unit'));
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
                    ->where('company_id', $this->activeCompanyId())
                    ->orWhereNull('company_id')),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Unit $unit */
            $unit = $this->route('unit');

            if ((int) $this->input('base_unit_id') === $unit->id) {
                $validator->errors()->add('base_unit_id', 'Una unidad no puede ser su propia unidad base.');
            }
        });
    }
}
