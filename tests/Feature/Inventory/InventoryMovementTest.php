<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

function makeInventoryFixture(array $productOverrides = []): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'cost' => '10.0000',
        ...$productOverrides,
    ]);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    return compact('company', 'branch', 'warehouse', 'product', 'admin');
}

test('recording a movement creates the movement row and updates the balance', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeInventoryFixture();

    $movement = app(RecordInventoryMovementAction::class)->execute([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'movement_type' => InventoryMovementType::Initial,
        'quantity' => '10',
        'unit_cost' => '10',
        'performed_by' => $admin->id,
    ]);

    expect($movement->previous_stock)->toBe('0.0000')
        ->and($movement->resulting_stock)->toBe('10.0000');

    $balance = InventoryBalance::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
    expect($balance->quantity)->toBe('10.0000');
    expect(InventoryMovement::count())->toBe(1);
});

test('a positive adjustment increases stock', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeInventoryFixture();

    app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::AdjustmentIn, '15', 'Entrada manual', null, $admin);

    expect(InventoryBalance::where('product_id', $product->id)->first()->quantity)->toBe('15.0000');
});

test('a negative adjustment decreases stock', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeInventoryFixture();

    app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::Initial, '20', 'Inicial', null, $admin);
    app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::AdjustmentOut, '5', 'Merma', null, $admin);

    expect(InventoryBalance::where('product_id', $product->id)->first()->quantity)->toBe('15.0000');
});

test('negative stock is blocked by default', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeInventoryFixture(['allows_negative_stock' => false]);

    app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::Initial, '5', 'Inicial', null, $admin);

    expect(fn () => app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::AdjustmentOut, '10', 'Merma', null, $admin))
        ->toThrow(InsufficientStockException::class);

    // The rejected movement must not have been persisted, and the balance stays untouched.
    expect(InventoryMovement::where('movement_type', InventoryMovementType::AdjustmentOut)->count())->toBe(0);
    expect(InventoryBalance::where('product_id', $product->id)->first()->quantity)->toBe('5.0000');
});

test('negative stock is allowed when the product explicitly permits it', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'warehouse' => $warehouse, 'product' => $product, 'admin' => $admin] = makeInventoryFixture(['allows_negative_stock' => true]);

    app(AdjustStockAction::class)->execute($warehouse, $product, null, null, InventoryMovementType::AdjustmentOut, '10', 'Venta sin stock', null, $admin);

    expect(InventoryBalance::where('product_id', $product->id)->first()->quantity)->toBe('-10.0000');
});

test('a movement cannot mix a product and a warehouse from different companies', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'product' => $product, 'admin' => $admin] = makeInventoryFixture();

    $foreignWarehouse = Warehouse::factory()->create();

    expect(fn () => app(RecordInventoryMovementAction::class)->execute([
        'company_id' => $company->id,
        'branch_id' => $foreignWarehouse->branch_id,
        'warehouse_id' => $foreignWarehouse->id,
        'product_id' => $product->id,
        'movement_type' => InventoryMovementType::Initial,
        'quantity' => '1',
        'unit_cost' => '1',
        'performed_by' => $admin->id,
    ]))->toThrow(InvalidArgumentException::class);
});

test('a cajero cannot register a stock adjustment', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'branch' => $branch, 'warehouse' => $warehouse, 'product' => $product] = makeInventoryFixture();

    $cashier = User::factory()->create(['company_id' => $company->id]);
    $cashier->assignRole('Cajero');

    $this->actingAs($cashier)->post(route('inventory.adjustments.store'), [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'movement_type' => 'adjustment_in',
        'quantity' => '5',
        'reason' => 'Intento no autorizado',
    ])->assertForbidden();

    expect(InventoryMovement::count())->toBe(0);
});
