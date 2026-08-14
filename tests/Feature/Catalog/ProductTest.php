<?php

use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

function makeProductAdmin(): array
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    return [$company, $admin, $unit];
}

test('sku must be unique per company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin, $unit] = makeProductAdmin();

    Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'sku' => 'SKU-001']);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'unit_id' => $unit->id,
        'name' => 'Producto duplicado',
        'sku' => 'SKU-001',
        'product_type' => 'physical',
        'tracking_type' => 'simple',
        'cost' => '10',
        'sale_price' => '15',
        'minimum_stock' => '0',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('sku');
    expect(Product::where('company_id', $company->id)->where('sku', 'SKU-001')->count())->toBe(1);
});

test('a company admin cannot see or edit products from another company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin] = makeProductAdmin();

    $foreignProduct = Product::factory()->create();

    $this->actingAs($admin)
        ->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('products.data', []));

    $this->actingAs($admin)
        ->get(route('products.edit', $foreignProduct))
        ->assertNotFound();
});

test('creating a product with a category from another company fails validation', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin, $unit] = makeProductAdmin();

    $foreignCategory = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $foreignCategory->id,
        'unit_id' => $unit->id,
        'name' => 'Producto',
        'sku' => 'SKU-XCOM',
        'product_type' => 'physical',
        'tracking_type' => 'simple',
        'cost' => '10',
        'sale_price' => '15',
        'minimum_stock' => '0',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('category_id');
});

test('company admin can create a product', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin, $unit] = makeProductAdmin();

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'unit_id' => $unit->id,
        'name' => 'Producto nuevo',
        'sku' => 'SKU-NEW',
        'product_type' => 'physical',
        'tracking_type' => 'simple',
        'cost' => '10.5000',
        'sale_price' => '19.9900',
        'minimum_stock' => '5',
        'status' => 'active',
    ]);

    $product = Product::where('company_id', $company->id)->where('sku', 'SKU-NEW')->first();

    // Redirects into "editar" (not the index) so the "Ajustar existencias"
    // CTA is immediately reachable for the newly created product.
    $response->assertRedirect(route('products.edit', $product));

    expect($product)->not->toBeNull()
        ->and($product->cost)->toBe('10.5000')
        ->and($product->sale_price)->toBe('19.9900');
});

test('updating a product does not change its price or cost', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin, $unit] = makeProductAdmin();

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'sale_price' => '50.0000',
        'cost' => '30.0000',
    ]);

    $this->actingAs($admin)->put(route('products.update', $product), [
        'unit_id' => $unit->id,
        'name' => 'Nombre actualizado',
        'sku' => $product->sku,
        'product_type' => 'physical',
        'tracking_type' => 'simple',
        'minimum_stock' => '0',
        'status' => 'active',
        // Even if a client tries to sneak price/cost through the general update, it must be ignored.
        'sale_price' => '999.0000',
        'cost' => '999.0000',
    ])->assertRedirect(route('products.index'));

    $product->refresh();
    expect($product->name)->toBe('Nombre actualizado')
        ->and($product->sale_price)->toBe('50.0000')
        ->and($product->cost)->toBe('30.0000');
});

test('product barcode must be unique per company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin, $unit] = makeProductAdmin();

    $existing = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);
    ProductBarcode::create([
        'company_id' => $company->id,
        'product_id' => $existing->id,
        'barcode' => '7501111111111',
        'type' => 'EAN13',
        'is_primary' => true,
        'quantity_multiplier' => 1,
    ]);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'unit_id' => $unit->id,
        'name' => 'Otro producto',
        'sku' => 'SKU-BC',
        'product_type' => 'physical',
        'tracking_type' => 'simple',
        'cost' => '10',
        'sale_price' => '15',
        'minimum_stock' => '0',
        'status' => 'active',
        'barcodes' => [
            ['barcode' => '7501111111111', 'type' => 'EAN13', 'is_primary' => true, 'quantity_multiplier' => 1],
        ],
    ]);

    $response->assertSessionHasErrors('barcodes.0.barcode');
});

test('a product with inventory movements cannot be deleted', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin, $unit] = makeProductAdmin();

    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);

    app(RecordInventoryMovementAction::class)->execute([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'movement_type' => InventoryMovementType::Initial,
        'quantity' => '10',
        'unit_cost' => '5',
        'performed_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('products.destroy', $product))
        ->assertSessionHasErrors();

    expect(Product::find($product->id))->not->toBeNull();
});
