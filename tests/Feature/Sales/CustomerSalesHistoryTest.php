<?php

use App\Models\Sale;
use Illuminate\Support\Str;

test('the sales history page shows correct stats and a flat list of sales for the customer', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '2']],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => '40']],
    ])->assertCreated();

    $sale = Sale::firstOrFail();

    $this->actingAs($fixture['admin'])
        ->get(route('customers.sales-history', $fixture['customer']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/Customers/SalesHistory')
            ->where('customer.id', $fixture['customer']->id)
            ->where('customer.name', $fixture['customer']->name)
            ->has('sales.data', 1)
            ->where('sales.data.0.id', $sale->id)
            ->where('stats.total_purchased', '40.00')
            ->where('stats.average_ticket', '40.00')
            ->whereNot('stats.last_purchase_at', null));
});

test('a customer with no sales shows an empty history with zeroed stats', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('customers.sales-history', $fixture['customer']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sales.data', 0)
            ->where('stats.total_purchased', '0.00')
            ->where('stats.average_ticket', '0.00')
            ->where('stats.last_purchase_at', null));
});
