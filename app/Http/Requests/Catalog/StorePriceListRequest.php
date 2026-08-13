<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Status;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\PriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceListRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('create', PriceList::class);
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
                Rule::unique('price_lists', 'code')->where('company_id', $this->activeCompanyId()),
            ],
            'currency' => ['required', 'string', 'size:3'],
            'priority' => ['integer', 'min:0'],
            'is_default' => ['boolean'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
