<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

test('a lot can be created with an expiration date and initial stock', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'tracking_type' => 'expiration']);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)->post(route('inventory.lots.store'), [
        'product_id' => $product->id,
        'lot_number' => 'LOTE-001',
        'expiration_date' => now()->addDays(10)->toDateString(),
        'cost' => '10.0000',
        'status' => 'active',
        'warehouse_id' => $warehouse->id,
        'initial_quantity' => '40',
    ])->assertRedirect();

    $lot = ProductLot::where('company_id', $company->id)->where('lot_number', 'LOTE-001')->firstOrFail();
    expect($lot->isExpired())->toBeFalse();

    $balance = InventoryBalance::where('product_lot_id', $lot->id)->first();
    expect($balance->quantity)->toBe('40.0000');
});

test('an expired lot is flagged as expired', function () {
    $company = Company::factory()->create();
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);

    $lot = ProductLot::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'expiration_date' => now()->subDay(),
    ]);

    expect($lot->isExpired())->toBeTrue();
});

test('lot number must be unique per product and company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    ProductLot::factory()->create(['company_id' => $company->id, 'product_id' => $product->id, 'lot_number' => 'DUP-001']);

    $this->actingAs($admin)->post(route('inventory.lots.store'), [
        'product_id' => $product->id,
        'lot_number' => 'DUP-001',
        'cost' => '5.0000',
        'status' => 'active',
    ])->assertSessionHasErrors('lot_number');

    expect(ProductLot::where('company_id', $company->id)->where('lot_number', 'DUP-001')->count())->toBe(1);
});
