<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Str;

function checkoutPayload(array $fixture, array $overrides = []): array
{
    return array_replace([
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => null,
        'customer_id' => $fixture['customer']->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '2'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '40'],
        ],
    ], $overrides);
}

test('a sale requires an open cash session when the company demands it', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.store'), checkoutPayload($fixture))
        ->assertUnprocessable();

    expect(Sale::count())->toBe(0);
});

test('a cash sale completes, records a cash movement, and deducts inventory', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $response = $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.store'), checkoutPayload($fixture, ['cash_session_id' => $session->id]))
        ->assertCreated();

    $sale = Sale::firstOrFail();
    expect($sale->status->value)->toBe('completed')
        ->and($sale->total)->toBe('40.0000')
        ->and($sale->amount_received)->toBe('40.0000')
        ->and($sale->change_amount)->toBe('0.0000');

    expect($response->json('data.folio'))->toBe($sale->folio);

    $balance = InventoryBalance::where('product_id', $fixture['product']->id)->where('warehouse_id', $fixture['warehouse']->id)->firstOrFail();
    expect($balance->quantity)->toBe('98.0000');

    $movement = InventoryMovement::where('product_id', $fixture['product']->id)->where('movement_type', 'sale')->firstOrFail();
    expect($movement->quantity)->toBe('2.0000')->and($movement->direction->value)->toBe('out');

    $cashMovement = CashMovement::where('cash_session_id', $session->id)->where('type', 'sale')->first();
    expect($cashMovement)->not->toBeNull()
        ->and($cashMovement->amount)->toBe('40.0000');
});

test('a card payment does not generate a cash movement', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'payments' => [
            ['payment_method_id' => $fixture['card']->id, 'amount' => '40', 'reference' => 'AUTH123'],
        ],
    ]))->assertCreated();

    expect(CashMovement::where('cash_session_id', $session->id)->where('type', 'sale')->count())->toBe(0);

    $payment = SalePayment::firstOrFail();
    expect($payment->payment_method_id)->toBe($fixture['card']->id)
        ->and($payment->reference)->toBe('AUTH123');
});

test('a combined cash and card payment splits correctly and computes change only from cash', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    // total is 40 (2 x 20); pay 25 by card + 20 cash -> change should be 5
    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'payments' => [
            ['payment_method_id' => $fixture['card']->id, 'amount' => '25'],
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '20'],
        ],
    ]))->assertCreated();

    $sale = Sale::firstOrFail();
    expect($sale->amount_received)->toBe('45.0000')
        ->and($sale->change_amount)->toBe('5.0000');

    $cashMovement = CashMovement::where('cash_session_id', $session->id)->where('type', 'sale')->firstOrFail();
    // net cash kept in the drawer = 20 paid - 5 change = 15
    expect($cashMovement->amount)->toBe('15.0000');
});

test('insufficient payment is rejected and no sale is created', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '10'],
        ],
    ]))->assertUnprocessable();

    expect(Sale::count())->toBe(0);
});

test('a price manipulated from the frontend is ignored and recalculated server-side', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    // The item payload has no price field at all — the server always resolves
    // it from the product. This test proves a client cannot inject one: even
    // if extra unexpected keys are sent, validated() only picks known fields.
    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '2', 'unit_price' => '0.01'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '40'],
        ],
    ]))->assertCreated();

    $sale = Sale::firstOrFail();
    expect($sale->total)->toBe('40.0000'); // still 2 x 20, not 2 x 0.01
});

test('a sale is rejected when stock is insufficient and negative stock is not allowed', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '9999'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '999999'],
        ],
    ]))->assertUnprocessable();

    expect(Sale::count())->toBe(0);
    expect(InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity)->toBe('100.0000');
});

test('a piece-tracked product rejects fractional quantities', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '1.5'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '30'],
        ],
    ]))->assertUnprocessable();

    expect(Sale::count())->toBe(0);
});

test('a bulk product allows fractional quantities', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $kg = Unit::withoutGlobalScopes()->where('symbol', 'KG')->first()
        ?? Unit::factory()->create(['company_id' => null, 'symbol' => 'KG', 'allows_fraction' => true]);
    $bulk = Product::factory()->create([
        'company_id' => $fixture['company']->id,
        'unit_id' => $kg->id,
        'cost' => '10.0000',
        'sale_price' => '20.0000',
        'status' => 'active',
        'visible_in_pos' => true,
        'tracking_type' => 'simple',
    ]);
    app(AdjustStockAction::class)->execute(
        $fixture['warehouse'], $bulk, null, null, InventoryMovementType::Initial, '50', 'Inicial', null, $fixture['admin'],
    );

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $bulk->id, 'quantity' => '1.5'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '30'],
        ],
    ]))->assertCreated();

    expect(InventoryBalance::where('product_id', $bulk->id)->first()->quantity)->toBe('48.5000');
});

test('a variant sale deducts the correct variant balance', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $variantProduct = Product::factory()->create([
        'company_id' => $fixture['company']->id,
        'unit_id' => $fixture['unit']->id,
        'tracking_type' => 'variants',
        'status' => 'active',
        'visible_in_pos' => true,
    ]);
    $variant = ProductVariant::factory()->create([
        'company_id' => $fixture['company']->id,
        'product_id' => $variantProduct->id,
        'sale_price' => '35.0000',
        'cost' => '20.0000',
    ]);
    app(AdjustStockAction::class)->execute(
        $fixture['warehouse'], $variantProduct, $variant, null, InventoryMovementType::Initial, '10', 'Inicial', null, $fixture['admin'],
    );

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $variantProduct->id, 'product_variant_id' => $variant->id, 'quantity' => '3'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '105'],
        ],
    ]))->assertCreated();

    $balance = InventoryBalance::where('product_variant_id', $variant->id)->firstOrFail();
    expect($balance->quantity)->toBe('7.0000');

    $sale = Sale::firstOrFail();
    expect($sale->total)->toBe('105.0000');
});

test('a lot-tracked sale selects the lot that expires first (FEFO)', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $lotProduct = Product::factory()->create([
        'company_id' => $fixture['company']->id,
        'unit_id' => $fixture['unit']->id,
        'tracking_type' => 'expiration',
        'status' => 'active',
        'visible_in_pos' => true,
        'sale_price' => '10.0000',
        'cost' => '5.0000',
    ]);

    $farLot = ProductLot::factory()->create([
        'company_id' => $fixture['company']->id, 'product_id' => $lotProduct->id,
        'lot_number' => 'FAR', 'expiration_date' => now()->addMonths(6),
    ]);
    $nearLot = ProductLot::factory()->create([
        'company_id' => $fixture['company']->id, 'product_id' => $lotProduct->id,
        'lot_number' => 'NEAR', 'expiration_date' => now()->addDays(5),
    ]);

    app(AdjustStockAction::class)->execute($fixture['warehouse'], $lotProduct, null, $farLot, InventoryMovementType::Initial, '20', 'Inicial', null, $fixture['admin']);
    app(AdjustStockAction::class)->execute($fixture['warehouse'], $lotProduct, null, $nearLot, InventoryMovementType::Initial, '20', 'Inicial', null, $fixture['admin']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $lotProduct->id, 'quantity' => '5'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '50'],
        ],
    ]))->assertCreated();

    expect(InventoryBalance::where('product_lot_id', $nearLot->id)->first()->quantity)->toBe('15.0000')
        ->and(InventoryBalance::where('product_lot_id', $farLot->id)->first()->quantity)->toBe('20.0000');
});

test('a permitted discount is applied and reflected in the sale total', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);
    $fixture['cashier']->givePermissionTo('discounts.apply');

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '1', 'discount_type' => 'fixed', 'discount_value' => '2'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '18'],
        ],
    ]))->assertCreated();

    $sale = Sale::firstOrFail();
    expect($sale->total)->toBe('18.0000')->and($sale->discount_total)->toBe('2.0000');
});

test('a discount that drops the price below the minimum is rejected without discounts.authorize', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    // product minimum_price is 15; a 10 fixed discount on a 20 unit price -> net 10, below minimum
    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'cash_session_id' => $session->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '1', 'discount_type' => 'fixed', 'discount_value' => '10'],
        ],
        'payments' => [
            ['payment_method_id' => $fixture['cash']->id, 'amount' => '10'],
        ],
    ]))->assertUnprocessable();

    expect(Sale::count())->toBe(0);
});

test('resubmitting the same checkout_uuid does not create a duplicate sale', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);
    $payload = checkoutPayload($fixture, ['cash_session_id' => $session->id]);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), $payload)->assertCreated();
    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), $payload)->assertCreated();

    expect(Sale::where('checkout_uuid', $payload['checkout_uuid'])->count())->toBe(1);
    expect(InventoryBalance::where('product_id', $fixture['product']->id)->first()->quantity)->toBe('98.0000');
});

test('sales are isolated between companies', function () {
    $fixtureA = posFixture();
    $fixtureB = posFixture();

    $sessionA = openPosSession($fixtureA['register'], $fixtureA['cashier']);
    $this->actingAs($fixtureA['cashier'])->postJson(route('sales.store'), checkoutPayload($fixtureA, ['cash_session_id' => $sessionA->id]))->assertCreated();

    $sale = Sale::firstOrFail();

    $this->actingAs($fixtureB['admin'])->get(route('sales.show', $sale))->assertNotFound();
});

test('a user restricted to another branch cannot check out on a register outside their branch', function () {
    $fixture = posFixture();
    $otherBranch = Branch::factory()->create(['company_id' => $fixture['company']->id]);
    $otherWarehouse = Warehouse::factory()->create(['branch_id' => $otherBranch->id, 'company_id' => $fixture['company']->id]);
    $otherRegister = CashRegister::factory()->create([
        'branch_id' => $otherBranch->id, 'company_id' => $fixture['company']->id, 'warehouse_id' => $otherWarehouse->id,
    ]);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), checkoutPayload($fixture, [
        'register_id' => $otherRegister->id,
        'warehouse_id' => $otherWarehouse->id,
    ]))->assertForbidden();

    expect(Sale::count())->toBe(0);
});

test('a cashier without products.view-costs does not see cost or profit in the sale response', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $response = $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.store'), checkoutPayload($fixture, ['cash_session_id' => $session->id]))
        ->assertCreated();

    expect($response->json('data.cost_total'))->toBeNull()
        ->and($response->json('data.profit_total'))->toBeNull();
});
