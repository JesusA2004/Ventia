<?php

namespace App\Http\Requests\Inventory;

use App\Enums\Status;
use App\Models\ProductLot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lot'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        /** @var ProductLot $lot */
        $lot = $this->route('lot');

        return [
            'lot_number' => [
                'required', 'string', 'max:255',
                Rule::unique('product_lots', 'lot_number')
                    ->where('company_id', $companyId)
                    ->where('product_id', $lot->product_id)
                    ->ignore($lot->id),
            ],
            'manufacture_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:manufacture_date'],
            'received_at' => ['nullable', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }
}
