<?php

use App\Actions\Cash\CloseCashSessionAction;
use App\Models\User;

test('closing a cash session computes expected cash, counted cash, and the difference', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.movements.store', $session), [
        'type' => 'deposit',
        'amount' => '50',
        'reason' => 'Depósito extra',
    ]);

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.close', $session), [
        'counted_cash' => '540',
        'closing_notes' => 'Faltante de 10 pesos',
    ])->assertRedirect();

    $session->refresh();
    expect($session->status->value)->toBe('closed')
        ->and($session->expected_cash)->toBe('550.0000')
        ->and($session->counted_cash)->toBe('540.0000')
        ->and($session->difference)->toBe('-10.0000');
});

test('a cash session cannot be closed twice', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    app(CloseCashSessionAction::class)->execute($session, '500', null, $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.close', $session), [
        'counted_cash' => '500',
    ])->assertSessionHasErrors();

    expect($session->fresh()->status->value)->toBe('closed');
});

test('a user cannot close another cashiers session without cash.force-close', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $otherCashier = User::factory()->create(['company_id' => $fixture['company']->id]);
    $otherCashier->assignRole('Cajero');
    $otherCashier->branches()->sync([$fixture['branch']->id]);

    $this->actingAs($otherCashier)->post(route('cash.sessions.close', $session), [
        'counted_cash' => '500',
    ])->assertForbidden();

    expect($session->fresh()->status->value)->toBe('open');
});

test('a manager with cash.force-close can close another cashiers session', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['admin'])->post(route('cash.sessions.close', $session), [
        'counted_cash' => '500',
    ])->assertRedirect();

    expect($session->fresh()->status->value)->toBe('closed');
});
