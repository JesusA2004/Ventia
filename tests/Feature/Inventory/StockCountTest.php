<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Enums\StockCountStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

function makeStockCountFixture(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::Initial, '100', 'Inicial', null, $admin);

    return compact('company', 'branch', 'warehouse', 'product', 'admin');
}

test('a stock count freezes the expected quantity, completes and applies the difference once', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeStockCountFixture();

    $this->actingAs($admin);

    $this->post(route('inventory.counts.store'), [
        'warehouse_id' => $warehouse->id,
        'products' => [['product_id' => $product->id]],
    ])->assertRedirect();

    $count = StockCount::firstOrFail();
    expect($count->status)->toBe(StockCountStatus::Counting);
    $item = $count->items()->first();
    expect($item->expected_quantity)->toBe('100.0000');

    $this->post(route('inventory.counts.complete', $count), [
        'counted' => [$item->id => '95'],
    ])->assertRedirect();

    $item->refresh();
    expect($count->fresh()->status)->toBe(StockCountStatus::Completed)
        ->and($item->counted_quantity)->toBe('95.0000')
        ->and($item->difference)->toBe('-5.0000');

    $this->post(route('inventory.counts.apply', $count))->assertRedirect();

    expect($count->fresh()->status)->toBe(StockCountStatus::Applied);
    expect(InventoryBalance::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first()->quantity)
        ->toBe('95.0000');
});

test('applying a stock count twice is rejected and does not double-adjust stock', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeStockCountFixture();

    $this->actingAs($admin);

    $this->post(route('inventory.counts.store'), [
        'warehouse_id' => $warehouse->id,
        'products' => [['product_id' => $product->id]],
    ]);
    $count = StockCount::firstOrFail();
    $item = $count->items()->first();

    $this->post(route('inventory.counts.complete', $count), ['counted' => [$item->id => '90']]);
    $this->post(route('inventory.counts.apply', $count))->assertRedirect();

    $balanceAfterFirstApply = InventoryBalance::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first()->quantity;

    $this->post(route('inventory.counts.apply', $count))->assertSessionHasErrors();

    expect(InventoryBalance::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first()->quantity)
        ->toBe($balanceAfterFirstApply);
});
