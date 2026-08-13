<?php

namespace App\Http\Requests\Catalog;

use App\Enums\BarcodeType;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TrackingType;
use App\Http\Requests\Concerns\ResolvesActiveCompany;
use App\Models\ProductAttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    use ResolvesActiveCompany;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->activeCompanyId();
        $productId = $this->route('product');

        $rules = [
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
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->where('company_id', $companyId)->ignore($productId)],
            'internal_code' => ['nullable', 'string', 'max:255', Rule::unique('products', 'internal_code')->where('company_id', $companyId)->ignore($productId)],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'tracking_type' => ['required', Rule::enum(TrackingType::class)],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'allows_negative_stock' => ['boolean'],
            'visible_in_pos' => ['boolean'],
            'is_favorite' => ['boolean'],
            'status' => ['required', Rule::enum(Status::class)],

            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('company_id', $companyId)],
            'variants.*.internal_code' => ['nullable', 'string', 'max:255'],
            'variants.*.status' => ['nullable', Rule::enum(Status::class)],
            'variants.*.attribute_value_ids' => ['array'],
            'variants.*.attribute_value_ids.*' => ['integer', 'exists:product_attribute_values,id'],

            'barcodes' => ['array'],
            'barcodes.*.id' => ['nullable', 'integer', Rule::exists('product_barcodes', 'id')->where('company_id', $companyId)],
            'barcodes.*.type' => ['required', Rule::enum(BarcodeType::class)],
            'barcodes.*.is_primary' => ['boolean'],
            'barcodes.*.quantity_multiplier' => ['nullable', 'numeric', 'min:0.0001'],
        ];

        foreach ($this->input('variants', []) as $index => $variant) {
            $rules["variants.{$index}.sku"] = [
                'required', 'string', 'max:255',
                Rule::unique('product_variants', 'sku')->where('company_id', $companyId)->ignore($variant['id'] ?? null),
            ];
        }

        foreach ($this->input('barcodes', []) as $index => $barcode) {
            $rules["barcodes.{$index}.barcode"] = [
                'required', 'string', 'max:255',
                Rule::unique('product_barcodes', 'barcode')->where('company_id', $companyId)->ignore($barcode['id'] ?? null),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $companyId = $this->activeCompanyId();

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
