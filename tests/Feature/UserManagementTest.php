<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('company admin can create a user with a role and branch access', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Nuevo Cajero',
        'email' => 'nuevo.cajero@example.com',
        'password' => 'password123',
        'is_active' => true,
        'role' => 'Cajero',
        'branch_ids' => [$branch->id],
    ]);

    $response->assertRedirect(route('users.index'));

    $created = User::where('email', 'nuevo.cajero@example.com')->firstOrFail();

    expect($created->company_id)->toBe($company->id)
        ->and($created->hasRole('Cajero'))->toBeTrue()
        ->and($created->branches()->pluck('branches.id')->all())->toBe([$branch->id]);
});

test('a company admin cannot manage users from another company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $otherCompanyUser = User::factory()->create();

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)
        ->get(route('users.edit', $otherCompanyUser))
        ->assertNotFound();
});

test('deleting a user soft deletes it instead of removing the record', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $target = User::factory()->create(['company_id' => $company->id]);
    $target->assignRole('Cajero');

    $this->actingAs($admin)->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->trashed())->toBeTrue();
});
