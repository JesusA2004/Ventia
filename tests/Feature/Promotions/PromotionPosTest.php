<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Str;

function completeSalePayload(array $fixture, array $overrides = []): array
{
    return array_merge([
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $overrides['cash_session_id'] ?? openPosSession($fixture['register'], $fixture['cashier'])->id,
        'customer_id' => $fixture['customer']->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '1']],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => '20']],
    ], $overrides);
}

test('an eligible automatic promotion discounts the sale and is snapshotted', function () {
    $fixture = posFixture();
    Promotion::factory()->for($fixture['company'])->create([
        'name' => '10% en todo', 'type' => 'percentage', 'value' => '10.0000',
    ]);

    $response = $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), completeSalePayload($fixture))
        ->assertCreated();

    $sale = Sale::findOrFail($response->json('data.id'));

    expect($sale->promotion_id)->not->toBeNull()
        ->and($sale->promotion_name_snapshot)->toBe('10% en todo')
        ->and($sale->promotion_discount_amount)->toBe('2.0000')
        ->and($sale->total)->toBe('18.0000');
});

test('a sale with no eligible promotion has zero promotion discount and completes normally', function () {
    $fixture = posFixture();

    $response = $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), completeSalePayload($fixture))
        ->assertCreated();

    $sale = Sale::findOrFail($response->json('data.id'));

    expect($sale->promotion_id)->toBeNull()
        ->and($sale->promotion_discount_amount)->toBe('0.0000')
        ->and($sale->coupon_id)->toBeNull()
        ->and($sale->total)->toBe('20.0000');
});

test('a promotion scoped to a category only discounts matching lines', function () {
    $fixture = posFixture();
    $category = Category::factory()->for($fixture['company'])->create();
    $fixture['product']->update(['category_id' => $category->id]);
    $otherProduct = Product::factory()->create([
        'company_id' => $fixture['company']->id, 'unit_id' => $fixture['unit']->id,
        'sale_price' => '50.0000', 'minimum_price' => '10.0000', 'status' => 'active', 'visible_in_pos' => true,
    ]);
    app(AdjustStockAction::class)->execute(
        $fixture['warehouse'], $otherProduct, null, null, InventoryMovementType::Initial, '100', 'Inicial', null, $fixture['admin'],
    );

    $promotion = Promotion::factory()->for($fixture['company'])->create(['type' => 'percentage', 'value' => '10.0000']);
    $promotion->categories()->attach($category->id);

    $response = $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), completeSalePayload($fixture, [
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '1'],
            ['product_id' => $otherProduct->id, 'quantity' => '1'],
        ],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => '70']],
    ]))->assertCreated();

    $sale = Sale::findOrFail($response->json('data.id'));

    // Only the $20 categorized product is discounted 10% ($2), not the $50 one.
    expect($sale->promotion_discount_amount)->toBe('2.0000')
        ->and($sale->total)->toBe('68.0000');
});

test('a valid coupon code discounts the sale and is snapshotted', function () {
    $fixture = posFixture();
    Coupon::factory()->for($fixture['company'])->create([
        'code' => 'verano10', 'name' => 'Verano', 'type' => 'fixed_amount', 'value' => '5.0000',
    ]);

    $response = $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), completeSalePayload($fixture, [
        'coupon_code' => 'Verano10',
    ]))->assertCreated();

    $sale = Sale::findOrFail($response->json('data.id'));

    expect($sale->coupon_id)->not->toBeNull()
        ->and($sale->coupon_code_snapshot)->toBe('VERANO10')
        ->and($sale->coupon_discount_amount)->toBe('5.0000')
        ->and($sale->total)->toBe('15.0000');
});

test('an exhausted coupon is silently not applied instead of blocking the sale', function () {
    $fixture = posFixture();
    $coupon = Coupon::factory()->for($fixture['company'])->create([
        'code' => 'UNICO', 'type' => 'fixed_amount', 'value' => '5.0000', 'usage_limit' => 1,
    ]);
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.store'), completeSalePayload($fixture, ['cash_session_id' => $session->id, 'checkout_uuid' => (string) Str::uuid(), 'coupon_code' => 'UNICO']))
        ->assertCreated();

    $second = $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.store'), completeSalePayload($fixture, ['cash_session_id' => $session->id, 'checkout_uuid' => (string) Str::uuid(), 'coupon_code' => 'UNICO']))
        ->assertCreated();

    $secondSale = Sale::findOrFail($second->json('data.id'));

    expect($secondSale->coupon_id)->toBeNull()
        ->and($secondSale->coupon_discount_amount)->toBe('0.0000')
        ->and($secondSale->total)->toBe('20.0000');

    expect(Sale::where('coupon_id', $coupon->id)->count())->toBe(1);
});

test('a coupon from another company never matches, even with the exact same code', function () {
    $fixtureA = posFixture();
    $fixtureB = posFixture();
    Coupon::factory()->for($fixtureB['company'])->create(['code' => 'SHARED', 'type' => 'fixed_amount', 'value' => '5.0000']);

    $response = $this->actingAs($fixtureA['cashier'])
        ->postJson(route('sales.store'), completeSalePayload($fixtureA, ['coupon_code' => 'SHARED']))
        ->assertCreated();

    $sale = Sale::findOrFail($response->json('data.id'));

    expect($sale->coupon_id)->toBeNull()
        ->and($sale->total)->toBe('20.0000');
});

test('a coupon that fully covers the total completes the sale with no payment required', function () {
    $fixture = posFixture();
    Coupon::factory()->for($fixture['company'])->create([
        'code' => 'GRATIS', 'type' => 'fixed_amount', 'value' => '100.0000',
    ]);

    $response = $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), completeSalePayload($fixture, [
        'coupon_code' => 'GRATIS',
        'payments' => [],
    ]))->assertCreated();

    $sale = Sale::findOrFail($response->json('data.id'));

    expect($sale->total)->toBe('0.0000')
        ->and($sale->coupon_discount_amount)->toBe('20.0000')
        ->and($sale->amount_received)->toBe('0.0000')
        ->and($sale->status->value)->toBe('completed');
});

test('a non-zero total still requires at least one payment', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['cashier'])
        ->postJson(route('sales.store'), completeSalePayload($fixture, ['payments' => []]))
        ->assertUnprocessable();

    expect(Sale::count())->toBe(0);
});

test('applying a coupon in the POS requires the discounts.apply permission', function () {
    $fixture = posFixture();
    // A cashier without discounts.apply: built with direct permission grants
    // (not the Cajero role, which already includes it) so the missing
    // permission is genuinely absent rather than shadowed by the role.
    $restrictedCashier = User::factory()->create(['company_id' => $fixture['company']->id]);
    $restrictedCashier->givePermissionTo(['pos.access', 'sales.create', 'cash.open']);
    $restrictedCashier->branches()->sync([$fixture['branch']->id]);
    $session = openPosSession($fixture['register'], $restrictedCashier);
    Coupon::factory()->for($fixture['company'])->create(['code' => 'NOPE', 'type' => 'fixed_amount', 'value' => '5.0000']);

    $this->actingAs($restrictedCashier)
        ->postJson(route('sales.store'), completeSalePayload($fixture, ['cash_session_id' => $session->id, 'coupon_code' => 'NOPE']))
        ->assertUnprocessable();

    expect(Sale::count())->toBe(0);
});
