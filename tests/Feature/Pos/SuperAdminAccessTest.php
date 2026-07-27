<?php

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Str;

test('a superadministrator can view sales and cash sessions from any company', function () {
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

    $sale = Sale::firstOrFail();

    $superadmin = User::factory()->create(['company_id' => null]);
    $superadmin->assignRole('Superadministrador');

    $this->actingAs($superadmin)->get(route('sales.show', $sale))->assertOk();
    $this->actingAs($superadmin)->get(route('cash.sessions.show', $session))->assertOk();
});
