<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('the audit log lists actions and their filter options with Spanish labels, not raw slugs', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superadmin = User::factory()->create(['company_id' => null]);
    $superadmin->assignRole('Superadministrador');

    AuditLog::create([
        'user_name' => 'María López', 'module' => 'inventory', 'action' => 'count_applied',
        'description' => 'Aplicó el conteo INV-000123.', 'created_at' => now(),
    ]);
    AuditLog::create([
        'user_name' => 'Juan Pérez', 'module' => 'cash', 'action' => 'opened',
        'description' => 'Abrió la caja «Caja 1» con 500.00.', 'created_at' => now(),
    ]);

    $props = $this->actingAs($superadmin)
        ->get(route('audit.index'))
        ->assertOk()
        ->inertiaPage()['props'];

    $actionLabels = collect($props['logs']['data'])->pluck('action_label', 'action');
    expect($actionLabels->get('count_applied'))->toBe('Conteo aplicado')
        ->and($actionLabels->get('opened'))->toBe('Caja abierta');

    $filterOptions = collect($props['filterOptions']['actions']);
    expect($filterOptions->firstWhere('value', 'count_applied'))->toBe(['value' => 'count_applied', 'label' => 'Conteo aplicado'])
        ->and($filterOptions->pluck('label'))->not->toContain('count_applied', 'opened');
});

test('a non-superadministrator cannot access the audit log', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create(['company_id' => Company::factory()->create()->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)->get(route('audit.index'))->assertForbidden();
});
