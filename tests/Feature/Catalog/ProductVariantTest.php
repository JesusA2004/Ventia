<?php

use App\Models\Company;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('company admin can create a product with variants', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    $attribute = ProductAttribute::create(['company_id' => $company->id, 'name' => 'Color', 'status' => 'active']);
    $red = ProductAttributeValue::create(['product_attribute_id' => $attribute->id, 'value' => 'Rojo', 'sort_order' => 0]);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'unit_id' => $unit->id,
        'name' => 'Producto con variantes',
        'sku' => 'VAR-BASE',
        'product_type' => 'physical',
        'tracking_type' => 'variants',
        'cost' => '10',
        'sale_price' => '20',
        'minimum_stock' => '0',
        'status' => 'active',
        'variants' => [
            [
                'sku' => 'VAR-BASE-ROJO',
                'cost' => '10',
                'sale_price' => '20',
                'status' => 'active',
                'attribute_value_ids' => [$red->id],
            ],
        ],
    ]);

    $variant = ProductVariant::where('company_id', $company->id)->where('sku', 'VAR-BASE-ROJO')->first();

    // Redirects into "editar" (not the index) so the "Ajustar existencias"
    // CTA is immediately reachable for the newly created product.
    $response->assertRedirect(route('products.edit', $variant->product_id));
    expect($variant)->not->toBeNull()
        ->and($variant->attributeValues()->pluck('product_attribute_values.id')->all())->toBe([$red->id]);
});

test('a variant sku from another company cannot be used to bypass isolation', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $otherCompanyVariant = ProductVariant::factory()->create(['sku' => 'SHARED-SKU']);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    // Same SKU text is fine across different companies (isolation, not a global namespace).
    $response = $this->actingAs($admin)->post(route('products.store'), [
        'unit_id' => $unit->id,
        'name' => 'Producto',
        'sku' => 'BASE-2',
        'product_type' => 'physical',
        'tracking_type' => 'variants',
        'cost' => '10',
        'sale_price' => '20',
        'minimum_stock' => '0',
        'status' => 'active',
        'variants' => [
            ['sku' => $otherCompanyVariant->sku, 'cost' => '10', 'sale_price' => '20', 'status' => 'active', 'attribute_value_ids' => []],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    expect(ProductVariant::where('company_id', $company->id)->where('sku', 'SHARED-SKU')->exists())->toBeTrue();
});
