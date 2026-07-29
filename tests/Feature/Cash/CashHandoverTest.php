<?php

use App\Enums\CashHandoverStatus;
use App\Models\CashHandover;
use App\Models\CashMovement;
use App\Services\SettingsService;

function enableCashHandoverRequired(int $companyId, bool $allowSelfApproval = false): void
{
    $settings = app(SettingsService::class);
    $settings->set($companyId, 'cash_handover_required', true);
    $settings->set($companyId, 'cash_handover_allow_self_approval', $allowSelfApproval);
}

/** @return array<int, array{denomination: int|float, quantity: int}> */
function denominationLines(string $total): array
{
    // 500 in a single $500 bill keeps the math trivial across tests.
    return [['denomination' => 500, 'quantity' => (int) ((float) $total / 500)]];
}

test('a cashier can request a supervised handover without closing the session', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
        'cashier_notes' => 'Turno tranquilo',
    ])->assertRedirect(route('cash.sessions.show', $session));

    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();
    expect($handover->status)->toBe(CashHandoverStatus::Pending)
        ->and($handover->expected_cash)->toBe('500.0000')
        ->and($handover->counted_cash)->toBe('500.0000')
        ->and($handover->difference)->toBe('0.0000')
        ->and($handover->cashier_notes)->toBe('Turno tranquilo');

    expect($session->fresh()->status->value)->toBe('open');
});

test('a cashier cannot request a second pending handover for the same session', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ])->assertRedirect();

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ])->assertSessionHasErrors('denominations');

    expect(CashHandover::where('cash_session_id', $session->id)->count())->toBe(1);
});

test('the cashier cannot skip the supervised flow using the regular close endpoint', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.close', $session), [
        'counted_cash' => '500',
    ])->assertForbidden();

    expect($session->fresh()->status->value)->toBe('open');
});

test('a supervisor with permission can approve a handover, closing the session exactly once', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['cashier'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
    ])->assertRedirect(route('cash.handovers.index'));

    $handover->refresh();
    $session->refresh();
    expect($handover->status)->toBe(CashHandoverStatus::Approved)
        ->and($handover->approved_by)->toBe($fixture['admin']->id)
        ->and($session->status->value)->toBe('closed')
        ->and($session->counted_cash)->toBe('500.0000')
        ->and(CashMovement::where('cash_session_id', $session->id)->where('type', 'closing')->count())->toBe(1);
});

test('a user without cash.approve-close cannot approve a handover', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['cashier'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['cashier']->email,
        'supervisor_password' => 'password',
    ])->assertSessionHasErrors('supervisor_email');

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::Pending);
});

test('resolving with the wrong password fails validation', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['cashier'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'wrong-password',
    ])->assertSessionHasErrors('supervisor_password');

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::Pending);
});

test('a cashier cannot self-approve their own handover when self-approval is disallowed', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id, allowSelfApproval: false);
    // The admin acts as their own cashier here since Cajero never has cash.approve-close.
    $session = openPosSession($fixture['register'], $fixture['admin'], '500');

    $this->actingAs($fixture['admin'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
    ])->assertSessionHasErrors('supervisor_email');

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::Pending);
});

test('a cashier can self-approve when self-approval is explicitly allowed', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id, allowSelfApproval: true);
    $session = openPosSession($fixture['register'], $fixture['admin'], '500');

    $this->actingAs($fixture['admin'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
    ])->assertRedirect();

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::Approved)
        ->and($session->fresh()->status->value)->toBe('closed');
});

test('a supervisor can reject a handover, leaving the session open', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'reject',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
        'notes' => 'El conteo no coincide con el reporte de turno.',
    ])->assertRedirect();

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::Rejected)
        ->and($session->fresh()->status->value)->toBe('open');
});

test('a supervisor can request a recount, after which the cashier can request a new handover', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'recount',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
        'notes' => 'Vuelve a contar la caja chica.',
    ])->assertRedirect();

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::RecountRequested)
        ->and($session->fresh()->status->value)->toBe('open');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ])->assertRedirect();

    expect(CashHandover::where('cash_session_id', $session->id)->where('status', CashHandoverStatus::Pending)->count())->toBe(1);
});

test('resolving an already-resolved handover a second time is idempotent, not a double transition', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
    ])->assertRedirect();

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
    ])->assertSessionHasErrors('decision');

    expect(CashMovement::where('cash_session_id', $session->id)->where('type', 'closing')->count())->toBe(1);
});

test('the expected cash, counted cash, and difference survive unaltered from request to resolution', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => [['denomination' => 500, 'quantity' => 1], ['denomination' => 20, 'quantity' => 2]],
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    expect($handover->expected_cash)->toBe('500.0000')
        ->and($handover->counted_cash)->toBe('540.0000')
        ->and($handover->difference)->toBe('40.0000');

    $this->actingAs($fixture['admin'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'password',
    ]);

    $handover->refresh();
    expect($handover->expected_cash)->toBe('500.0000')
        ->and($handover->counted_cash)->toBe('540.0000')
        ->and($handover->difference)->toBe('40.0000')
        ->and($session->fresh()->counted_cash)->toBe('540.0000');
});

test('supervisors can list pending handovers scoped to their company', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);

    $this->actingAs($fixture['admin'])->get(route('cash.handovers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Cash/Handovers/Index')
            ->has('handovers.data', 1)
        );

    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    $this->actingAs($fixture['admin'])->get(route('cash.handovers.show', $handover))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Cash/Handovers/Show')
            ->where('handover.status', 'pending')
            ->where('handover.branch_name', $fixture['branch']->name)
        );
});

test('repeated supervisor validation attempts are rate limited', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);
    $handover = CashHandover::where('cash_session_id', $session->id)->firstOrFail();

    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($fixture['cashier'])->post(route('cash.handovers.resolve', $handover), [
            'decision' => 'approve',
            'supervisor_email' => $fixture['admin']->email,
            'supervisor_password' => 'wrong-password',
        ])->assertSessionHasErrors('supervisor_password');
    }

    $this->actingAs($fixture['cashier'])->post(route('cash.handovers.resolve', $handover), [
        'decision' => 'approve',
        'supervisor_email' => $fixture['admin']->email,
        'supervisor_password' => 'wrong-password',
    ])->assertStatus(429);

    expect($handover->fresh()->status)->toBe(CashHandoverStatus::Pending);
});

test('a cashier without cash.approve-close or cash.receive-handover cannot list handovers', function () {
    $fixture = posFixture();
    enableCashHandoverRequired($fixture['company']->id);
    $session = openPosSession($fixture['register'], $fixture['cashier'], '500');

    $this->actingAs($fixture['cashier'])->post(route('cash.sessions.handover.store', $session), [
        'denominations' => denominationLines('500'),
    ]);

    $this->actingAs($fixture['cashier'])->get(route('cash.handovers.index'))->assertForbidden();
});
