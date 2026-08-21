<?php

use App\Support\AuditActionLabels;

test('known action slugs get their Spanish label', function () {
    expect(AuditActionLabels::label('count_started'))->toBe('Conteo iniciado')
        ->and(AuditActionLabels::label('count_applied'))->toBe('Conteo aplicado')
        ->and(AuditActionLabels::label('completed'))->toBe('Venta completada')
        ->and(AuditActionLabels::label('opened'))->toBe('Caja abierta')
        ->and(AuditActionLabels::label('login'))->toBe('Inicio de sesión')
        ->and(AuditActionLabels::label('active_company_changed'))->toBe('Cambio de empresa activa')
        ->and(AuditActionLabels::label('created'))->toBe('Creación');
});

test('an unmapped action slug still renders instead of erroring', function () {
    expect(AuditActionLabels::label('some_future_action'))->toBe('Some future action');
});

test('options() sorts by label and keeps the raw slug as the value', function () {
    $options = AuditActionLabels::options(['login', 'count_applied']);

    expect($options)->toBe([
        ['value' => 'count_applied', 'label' => 'Conteo aplicado'],
        ['value' => 'login', 'label' => 'Inicio de sesión'],
    ]);
});
