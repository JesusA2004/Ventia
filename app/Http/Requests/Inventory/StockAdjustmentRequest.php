<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory.adjust');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('company_id', $companyId)->where('product_id', $this->input('product_id')),
            ],
            'product_lot_id' => [
                'nullable',
                Rule::exists('product_lots', 'id')->where('company_id', $companyId)->where('product_id', $this->input('product_id')),
            ],
            'movement_type' => ['required', Rule::enum(InventoryMovementType::class)],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $productId = $this->input('product_id');
            $quantity = $this->input('quantity');

            if (! is_numeric($productId) || ! is_numeric($quantity)) {
                return;
            }

            $product = Product::query()->with('unit')->find((int) $productId);

            if ($product && ! $product->unit->allows_fraction && $this->hasFractionalPart((string) $quantity)) {
                $validator->errors()->add('quantity', "El producto «{$product->name}» se vende por pieza y no admite cantidades decimales.");
            }
        });
    }

    private function hasFractionalPart(string $quantity): bool
    {
        $parts = explode('.', $quantity);

        return isset($parts[1]) && rtrim($parts[1], '0') !== '';
    }
}
