<?php

use App\Models\Sale;
use Illuminate\Support\Str;

test('the summary tab shows sales KPIs for a completed sale in range', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '1']],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => '20']],
    ])->assertCreated();

    $this->actingAs($fixture['admin'])
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('tab', 'summary')
            ->where('data.kpis.1.value', '1'));
});

test('the sales tab breaks sales down by branch and payment method', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '1']],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => '20']],
    ])->assertCreated();

    $this->actingAs($fixture['admin'])
        ->get(route('reports.index', ['tab' => 'sales']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('tab', 'sales')
            ->where('data.tables.1.rows.0.0', $fixture['branch']->name));
});

test('a user without reports.view cannot access the reports module', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['cashier'])
        ->get(route('reports.index'))
        ->assertForbidden();
});

test('csv export returns the summary as a downloadable file', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('reports.export', ['tab' => 'summary']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect(Sale::count())->toBe(0);
});
