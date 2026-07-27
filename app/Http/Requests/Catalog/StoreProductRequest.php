<?php

namespace App\Http\Requests\Catalog;

use App\Enums\BarcodeType;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TrackingType;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->where('company_id', $companyId)],
            'unit_id' => [
                'required',
                Rule::exists('units', 'id')->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id')),
            ],
            'tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->where('company_id', $companyId)],
            'internal_code' => ['nullable', 'string', 'max:255', Rule::unique('products', 'internal_code')->where('company_id', $companyId)],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'tracking_type' => ['required', Rule::enum(TrackingType::class)],
            'cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'minimum_price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'allows_negative_stock' => ['boolean'],
            'visible_in_pos' => ['boolean'],
            'is_favorite' => ['boolean'],
            'status' => ['required', Rule::enum(Status::class)],

            'variants' => ['array'],
            'variants.*.sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->where('company_id', $companyId)],
            'variants.*.internal_code' => ['nullable', 'string', 'max:255'],
            'variants.*.cost' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.minimum_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['nullable', Rule::enum(Status::class)],
            'variants.*.attribute_value_ids' => ['array'],
            'variants.*.attribute_value_ids.*' => ['integer', 'exists:product_attribute_values,id'],

            'barcodes' => ['array'],
            'barcodes.*.barcode' => ['required', 'string', 'max:255', Rule::unique('product_barcodes', 'barcode')->where('company_id', $companyId)],
            'barcodes.*.type' => ['required', Rule::enum(BarcodeType::class)],
            'barcodes.*.is_primary' => ['boolean'],
            'barcodes.*.quantity_multiplier' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $companyId = $this->user()->company_id;

            foreach ($this->input('variants', []) as $index => $variant) {
                foreach ($variant['attribute_value_ids'] ?? [] as $valueId) {
                    $belongsToCompany = ProductAttributeValue::query()
                        ->whereKey($valueId)
                        ->whereHas('attribute', fn ($q) => $q->where('company_id', $companyId))
                        ->exists();

                    if (! $belongsToCompany) {
                        $validator->errors()->add("variants.{$index}.attribute_value_ids", 'Uno de los atributos seleccionados no pertenece a tu empresa.');
                    }
                }
            }
        });
    }
}
