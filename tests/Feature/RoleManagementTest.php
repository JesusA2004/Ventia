<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

test('company admin can update a role permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $role = Role::where('name', 'Cajero')->firstOrFail();

    $response = $this->actingAs($admin)->put(route('roles.update', $role), [
        'permissions' => ['products.view', 'sales.view'],
    ]);

    $response->assertRedirect(route('roles.index'));

    expect($role->fresh()->permissions()->pluck('name')->sort()->values()->all())
        ->toBe(['products.view', 'sales.view']);
});

test('the superadministrator role cannot be edited', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $role = Role::where('name', 'Superadministrador')->firstOrFail();

    $this->actingAs($admin)->get(route('roles.edit', $role))->assertForbidden();
    $this->actingAs($admin)->put(route('roles.update', $role), ['permissions' => []])->assertForbidden();
});

test('a user without roles.manage cannot access role management', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $cashier = User::factory()->create(['company_id' => $company->id]);
    $cashier->assignRole('Cajero');

    $this->actingAs($cashier)->get(route('roles.index'))->assertForbidden();
});
