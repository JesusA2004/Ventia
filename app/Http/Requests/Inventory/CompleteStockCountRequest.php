<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class CompleteStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('count'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'counted' => ['required', 'array', 'min:1'],
            'counted.*' => ['numeric', 'min:0'],
        ];
    }
}
