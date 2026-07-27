<?php

use App\Actions\Cash\CloseCashSessionAction;
use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use App\Services\Cash\CalculateExpectedCashService;

test('a cashier can open a cash session and it is persisted with an opening movement', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.store'), [
        'register_id' => $fixture['register']->id,
        'opening_amount' => '500',
        'opening_notes' => 'Fondo inicial del turno',
    ])->assertRedirect();

    $session = CashSession::where('register_id', $fixture['register']->id)->firstOrFail();
    expect($session->status->value)->toBe('open')
        ->and($session->opening_amount)->toBe('500.0000')
        ->and($session->user_id)->toBe($fixture['cashier']->id);

    $opening = CashMovement::where('cash_session_id', $session->id)->where('type', CashMovementType::Opening)->first();
    expect($opening)->not->toBeNull()
        ->and($opening->amount)->toBe('500.0000');
});

test('a register cannot have two open sessions at once', function () {
    $fixture = posFixture();
    openPosSession($fixture['register'], $fixture['cashier']);

    $other = User::factory()->create(['company_id' => $fixture['company']->id]);
    $other->assignRole('Cajero');
    $other->branches()->sync([$fixture['branch']->id]);

    $this->actingAs($other)->post(route('cash.sessions.store'), [
        'register_id' => $fixture['register']->id,
        'opening_amount' => '300',
    ])->assertSessionHasErrors();

    expect(CashSession::where('register_id', $fixture['register']->id)->where('status', 'open')->count())->toBe(1);
});

test('a user cannot open two sessions of their own simultaneously', function () {
    $fixture = posFixture();
    openPosSession($fixture['register'], $fixture['cashier']);

    $secondRegister = CashRegister::factory()->create([
        'branch_id' => $fixture['branch']->id, 'company_id' => $fixture['company']->id, 'warehouse_id' => $fixture['warehouse']->id,
    ]);

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.store'), [
        'register_id' => $secondRegister->id,
        'opening_amount' => '200',
    ])->assertSessionHasErrors();

    expect(CashSession::where('user_id', $fixture['cashier']->id)->where('status', 'open')->count())->toBe(1);
});

test('a user without cash.open permission cannot open a cash session', function () {
    $fixture = posFixture();
    $noAccess = User::factory()->create(['company_id' => $fixture['company']->id]);
    $noAccess->branches()->sync([$fixture['branch']->id]);

    $this->actingAs($noAccess)->post(route('cash.sessions.store'), [
        'register_id' => $fixture['register']->id,
        'opening_amount' => '500',
    ])->assertForbidden();

    expect(CashSession::count())->toBe(0);
});

test('a cash movement can be registered on an open session and reflects in the balance', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.movements.store', $session), [
        'type' => 'withdrawal',
        'amount' => '100',
        'reason' => 'Retiro para depósito bancario',
    ])->assertRedirect();

    expect(CashMovement::where('cash_session_id', $session->id)->where('type', 'withdrawal')->count())->toBe(1);

    $expected = app(CalculateExpectedCashService::class)->calculate($session->fresh());
    expect($expected)->toBe('400.0000'); // 500 opening - 100 withdrawal
});

test('movements cannot be registered on a closed cash session', function () {
    $fixture = posFixture();
    $session = openPosSession($fixture['register'], $fixture['cashier']);
    app(CloseCashSessionAction::class)->execute($session, '500', null, $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.movements.store', $session), [
        'type' => 'deposit',
        'amount' => '50',
        'reason' => 'Depósito tardío',
    ])->assertSessionHasErrors();

    expect(CashMovement::where('cash_session_id', $session->id)->where('type', 'deposit')->count())->toBe(0);
});
