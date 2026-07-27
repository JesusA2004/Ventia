<?php

use App\Models\CashMovement;
use App\Models\InventoryBalance;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Illuminate\Support\Str;

function completeSaleFixture(array $fixture, string $quantity = '5'): Sale
{
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    test()->actingAs($fixture['cashier'])->postJson(route('sales.store'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => $quantity]],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => (string) (20 * (float) $quantity)]],
    ])->assertCreated();

    return Sale::firstOrFail();
}

test('a manager can cancel a completed sale, which reverses inventory and cash', function () {
    $fixture = posFixture();
    $sale = completeSaleFixture($fixture);

    $balanceBefore = InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity;
    expect($balanceBefore)->toBe('95.0000');

    $this->actingAs($fixture['admin'])->post(route('sales.cancel', $sale), [
        'reason' => 'Cliente se arrepintió',
    ])->assertRedirect();

    expect($sale->fresh()->status->value)->toBe('cancelled');

    $balanceAfter = InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity;
    expect($balanceAfter)->toBe('100.0000');

    $refund = CashMovement::where('cash_session_id', $sale->cash_session_id)->where('type', 'refund')->first();
    expect($refund)->not->toBeNull()->and($refund->amount)->toBe('100.0000');
});

test('a sale cannot be cancelled twice', function () {
    $fixture = posFixture();
    $sale = completeSaleFixture($fixture);

    $this->actingAs($fixture['admin'])->post(route('sales.cancel', $sale), ['reason' => 'Primera cancelación']);

    $balanceAfterFirst = InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity;

    $this->actingAs($fixture['admin'])->post(route('sales.cancel', $sale), ['reason' => 'Segundo intento'])
        ->assertSessionHasErrors();

    expect(InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity)->toBe($balanceAfterFirst);
});

test('a cashier without sales.cancel permission cannot cancel a sale', function () {
    $fixture = posFixture();
    $sale = completeSaleFixture($fixture);

    $this->actingAs($fixture['cashier'])->post(route('sales.cancel', $sale), [
        'reason' => 'Intento no autorizado',
    ])->assertForbidden();

    expect($sale->fresh()->status->value)->toBe('completed');
});

test('a partial return restocks only the returned quantity and updates the sale status', function () {
    $fixture = posFixture();
    $sale = completeSaleFixture($fixture, '5');
    $item = SaleItem::where('sale_id', $sale->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('sales.returns.store', $sale), [
        'reason' => 'Producto dañado',
        'items' => [
            ['sale_item_id' => $item->id, 'quantity' => '2'],
        ],
    ])->assertRedirect();

    expect($sale->fresh()->status->value)->toBe('partially_returned');

    $balance = InventoryBalance::where('product_id', $fixture['product']->id)->first();
    expect($balance->quantity)->toBe('97.0000'); // 95 after sale + 2 returned

    $return = SaleReturn::firstOrFail();
    expect($return->total_refunded)->toBe('40.0000'); // 2 units x 20
});

test('a return cannot exceed the quantity available on the sale item', function () {
    $fixture = posFixture();
    $sale = completeSaleFixture($fixture, '3');
    $item = SaleItem::where('sale_id', $sale->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('sales.returns.store', $sale), [
        'reason' => 'Exceso',
        'items' => [
            ['sale_item_id' => $item->id, 'quantity' => '10'],
        ],
    ])->assertSessionHasErrors();

    expect(SaleReturn::count())->toBe(0);
});

test('a full return marks the sale as returned', function () {
    $fixture = posFixture();
    $sale = completeSaleFixture($fixture, '2');
    $item = SaleItem::where('sale_id', $sale->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('sales.returns.store', $sale), [
        'reason' => 'Devolución total',
        'items' => [
            ['sale_item_id' => $item->id, 'quantity' => '2'],
        ],
    ])->assertRedirect();

    expect($sale->fresh()->status->value)->toBe('returned');
});

test('cancellation is isolated between companies', function () {
    $fixtureA = posFixture();
    $fixtureB = posFixture();
    $sale = completeSaleFixture($fixtureA);

    $this->actingAs($fixtureB['admin'])->post(route('sales.cancel', $sale), [
        'reason' => 'No debería poder',
    ])->assertNotFound();

    expect($sale->fresh()->status->value)->toBe('completed');
});
