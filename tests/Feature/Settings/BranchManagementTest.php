<?php

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function makeCompanyAdmin(): User
{
    $company = Company::factory()->create();

    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('Administrador de empresa');

    return $user;
}

test('company admin can create a branch scoped to their company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = makeCompanyAdmin();

    $response = $this->actingAs($admin)->post(route('settings.branches.store'), [
        'name' => 'Sucursal Norte',
        'code' => 'SUC-NORTE',
        'address' => 'Calle 1',
        'phone' => '5512345678',
        'status' => Status::Active->value,
    ]);

    $response->assertRedirect(route('settings.branches.index'));

    expect(Branch::where('company_id', $admin->company_id)->where('code', 'SUC-NORTE')->exists())->toBeTrue();
});

test('a user cannot see or edit branches belonging to another company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $otherCompanyBranch = Branch::factory()->create();

    $admin = makeCompanyAdmin();

    $this->actingAs($admin)
        ->get(route('settings.branches.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('branches.data', []));

    $this->actingAs($admin)
        ->get(route('settings.branches.edit', $otherCompanyBranch))
        ->assertNotFound();
});

test('a user without the branches.manage permission is forbidden', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $cashier = User::factory()->create(['company_id' => $company->id]);
    $cashier->assignRole('Cajero');

    $this->actingAs($cashier)
        ->get(route('settings.branches.index'))
        ->assertForbidden();
});
