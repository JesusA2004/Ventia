<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('changing a product price records history and updates the base price', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'sale_price' => '100.0000']);

    $this->actingAs($admin)->patch(route('products.update-price', $product), [
        'price' => '120.0000',
        'reason' => 'Ajuste de temporada',
    ])->assertRedirect();

    $product->refresh();
    expect($product->sale_price)->toBe('120.0000');

    $history = ProductPriceHistory::where('product_id', $product->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->old_price)->toBe('100.0000')
        ->and($history->new_price)->toBe('120.0000')
        ->and($history->reason)->toBe('Ajuste de temporada')
        ->and($history->changed_by)->toBe($admin->id)
        ->and((float) $history->percentage_change)->toBe(20.0);
});

test('changing a product cost records history and updates the base cost', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'cost' => '50.0000']);

    $this->actingAs($admin)->patch(route('products.update-cost', $product), [
        'cost' => '55.0000',
        'reason' => 'Incremento del proveedor',
    ])->assertRedirect();

    $product->refresh();
    expect($product->cost)->toBe('55.0000');

    $history = ProductPriceHistory::where('product_id', $product->id)->first();
    expect($history->old_cost)->toBe('50.0000')
        ->and($history->new_cost)->toBe('55.0000');
});

test('a user without prices.edit cannot change the price', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $cashier = User::factory()->create(['company_id' => $company->id]);
    $cashier->assignRole('Cajero');
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id, 'sale_price' => '100.0000']);

    $this->actingAs($cashier)->patch(route('products.update-price', $product), [
        'price' => '200.0000',
        'reason' => 'Intento no autorizado',
    ])->assertForbidden();

    expect($product->fresh()->sale_price)->toBe('100.0000');
});
