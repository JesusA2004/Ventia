<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Sale;

test('a sale can be suspended without touching inventory or cash', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.suspend'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '3'],
        ],
    ])->assertCreated();

    $sale = Sale::firstOrFail();
    expect($sale->status->value)->toBe('suspended');

    // posFixture() itself registers one "initial stock" movement — suspending
    // must not add a second (sale) movement on top of it.
    expect(InventoryMovement::where('product_id', $fixture['product']->id)->count())->toBe(1);
    expect(InventoryMovement::where('product_id', $fixture['product']->id)->where('movement_type', 'sale')->count())->toBe(0);
    expect(InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity)->toBe('100.0000');
});

test('a suspended sale appears in the suspended list for its register', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.suspend'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '1']],
    ]);

    $response = $this->actingAs($fixture['cashier'])
        ->getJson(route('sales.suspended', ['register_id' => $fixture['register']->id]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

test('recovering a suspended sale revalidates stock and rebuilds it as a fresh draft', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.suspend'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '4']],
    ]);

    $suspended = Sale::firstOrFail();

    $response = $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.resume', $suspended))
        ->assertOk();

    expect(Sale::where('status', 'suspended')->count())->toBe(0);

    $fresh = Sale::firstOrFail();
    expect($fresh->status->value)->toBe('draft')
        ->and($fresh->items()->first()->quantity)->toBe('4.0000')
        ->and($response->json('data.id'))->toBe($fresh->id);
});

test('recovering a suspended sale fails if stock has since become insufficient', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.suspend'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '50']],
    ]);

    $suspended = Sale::firstOrFail();

    // Someone else sells almost all the stock in the meantime.
    app(AdjustStockAction::class)->execute(
        $fixture['warehouse'], $fixture['product'], null, null,
        InventoryMovementType::AdjustmentOut, '90', 'Merma', null, $fixture['admin'],
    );

    $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.resume', $suspended))
        ->assertUnprocessable();

    expect(Sale::where('status', 'suspended')->count())->toBe(1);
});

test('a suspended sale can be deleted by a user with sales.suspend permission', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.suspend'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '1']],
    ]);

    $suspended = Sale::firstOrFail();

    $this->actingAs($fixture['cashier'])
        ->delete(route('sales.destroy-suspended', $suspended))
        ->assertRedirect();

    expect(Sale::count())->toBe(0);
});
