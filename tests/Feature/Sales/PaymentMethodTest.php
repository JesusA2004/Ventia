<?php

use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('the index page exposes a flat, unwrapped array of payment methods (no fake pagination)', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    PaymentMethod::factory()->count(2)->create(['company_id' => $company->id]);

    $this->actingAs($admin)
        ->get(route('payment-methods.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/PaymentMethods/Index')
            ->has('paymentMethods', 2)
            ->has('paymentMethods.0.id')
            ->has('paymentMethods.0.name')
            ->missing('paymentMethods.data')
            ->missing('paymentMethods.meta'));
});

test('creating a payment method redirects back to the index', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)->post(route('payment-methods.store'), [
        'name' => 'Efectivo',
        'code' => 'CASH',
        'type' => 'cash',
        'status' => 'active',
    ])->assertRedirect(route('payment-methods.index'));

    expect(PaymentMethod::where('company_id', $company->id)->where('code', 'CASH')->exists())->toBeTrue();
});
