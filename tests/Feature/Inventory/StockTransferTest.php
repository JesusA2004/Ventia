<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Enums\StockTransferStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

function makeTransferFixture(): array
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $origin = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id, 'code' => 'ALM-A']);
    $destination = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id, 'code' => 'ALM-B']);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    app(AdjustStockAction::class)->execute($origin, $product, null, null, InventoryMovementType::Initial, '50', 'Inicial', null, $admin);

    return compact('company', 'branch', 'origin', 'destination', 'product', 'admin');
}

test('a full transfer moves stock from the origin warehouse to the destination warehouse', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['origin' => $origin, 'destination' => $destination, 'product' => $product, 'admin' => $admin] = makeTransferFixture();

    $this->actingAs($admin);

    $store = $this->post(route('inventory.transfers.store'), [
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $destination->id,
        'items' => [
            ['product_id' => $product->id, 'quantity_requested' => '20'],
        ],
    ]);
    $store->assertRedirect();

    $transfer = StockTransfer::firstOrFail();
    expect($transfer->status)->toBe(StockTransferStatus::Draft);

    $this->post(route('inventory.transfers.submit', $transfer))->assertRedirect();
    expect($transfer->fresh()->status)->toBe(StockTransferStatus::Pending);

    $this->post(route('inventory.transfers.approve', $transfer))->assertRedirect();
    expect($transfer->fresh()->status)->toBe(StockTransferStatus::Approved);

    $this->post(route('inventory.transfers.ship', $transfer))->assertRedirect();
    expect($transfer->fresh()->status)->toBe(StockTransferStatus::InTransit);
    expect(InventoryBalance::where('warehouse_id', $origin->id)->where('product_id', $product->id)->first()->quantity)->toBe('30.0000');

    $item = $transfer->items()->first();
    $this->post(route('inventory.transfers.receive', $transfer), [
        'received' => [$item->id => '20'],
    ])->assertRedirect();

    expect($transfer->fresh()->status)->toBe(StockTransferStatus::Received);
    expect(InventoryBalance::where('warehouse_id', $destination->id)->where('product_id', $product->id)->first()->quantity)->toBe('20.0000');
    expect(InventoryBalance::where('warehouse_id', $origin->id)->where('product_id', $product->id)->first()->quantity)->toBe('30.0000');
});

test('shipping a transfer twice is rejected instead of duplicating inventory movements', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['origin' => $origin, 'destination' => $destination, 'product' => $product, 'admin' => $admin] = makeTransferFixture();

    $this->actingAs($admin);

    $this->post(route('inventory.transfers.store'), [
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $destination->id,
        'items' => [['product_id' => $product->id, 'quantity_requested' => '10']],
    ]);

    $transfer = StockTransfer::firstOrFail();
    $this->post(route('inventory.transfers.submit', $transfer));
    $this->post(route('inventory.transfers.approve', $transfer));
    $this->post(route('inventory.transfers.ship', $transfer))->assertRedirect();

    $balanceAfterFirstShip = InventoryBalance::where('warehouse_id', $origin->id)->where('product_id', $product->id)->first()->quantity;

    // Second ship attempt must fail validation (wrong state) and must not move stock again.
    $this->post(route('inventory.transfers.ship', $transfer))->assertSessionHasErrors();

    expect(InventoryBalance::where('warehouse_id', $origin->id)->where('product_id', $product->id)->first()->quantity)
        ->toBe($balanceAfterFirstShip);
});

test('a transfer cannot be created with the same origin and destination warehouse', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['origin' => $origin, 'product' => $product, 'admin' => $admin] = makeTransferFixture();

    $this->actingAs($admin)->post(route('inventory.transfers.store'), [
        'origin_warehouse_id' => $origin->id,
        'destination_warehouse_id' => $origin->id,
        'items' => [['product_id' => $product->id, 'quantity_requested' => '5']],
    ])->assertSessionHasErrors('origin_warehouse_id');

    expect(StockTransfer::count())->toBe(0);
});
