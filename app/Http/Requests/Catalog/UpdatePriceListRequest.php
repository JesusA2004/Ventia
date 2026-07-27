<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('priceList'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('price_lists', 'code')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($this->route('priceList')),
            ],
            'currency' => ['required', 'string', 'size:3'],
            'priority' => ['integer', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
