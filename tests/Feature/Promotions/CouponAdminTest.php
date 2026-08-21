<?php

use App\Models\Company;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('an admin can create a coupon and the code is normalized', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)->post(route('promotions.coupons.store'), [
        'code' => '  verano10  ', 'name' => 'Verano', 'type' => 'fixed_amount', 'value' => '50', 'status' => 'active',
    ])->assertRedirect(route('promotions.coupons.index'));

    expect(Coupon::firstOrFail()->code)->toBe('VERANO10');
});

test('a coupon code must be unique within the company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $companyA = Company::factory()->create();
    $adminA = User::factory()->create(['company_id' => $companyA->id]);
    $adminA->assignRole('Administrador de empresa');
    Coupon::factory()->for($companyA)->create(['code' => 'DUPLICADO']);

    $this->actingAs($adminA)->post(route('promotions.coupons.store'), [
        'code' => 'duplicado', 'name' => 'Otro', 'type' => 'percentage', 'value' => '10', 'status' => 'active',
    ])->assertSessionHasErrors('code');

    expect(Coupon::where('company_id', $companyA->id)->count())->toBe(1);
});

// Separate test (not appended to the one above): ActiveCompanyContext is a
// scoped() singleton, memoized for the app's lifetime — correct for one
// real request, but making two actingAs()-as-different-companies calls
// inside a single test method reuses that memoized value across both,
// which isn't representative of how two real, separate HTTP requests behave.
test('a coupon code may repeat across different companies', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $companyA = Company::factory()->create();
    Coupon::factory()->for($companyA)->create(['code' => 'DUPLICADO']);

    $companyB = Company::factory()->create();
    $adminB = User::factory()->create(['company_id' => $companyB->id]);
    $adminB->assignRole('Administrador de empresa');

    $this->actingAs($adminB)->post(route('promotions.coupons.store'), [
        'code' => 'duplicado', 'name' => 'Empresa B', 'type' => 'percentage', 'value' => '10', 'status' => 'active',
    ])->assertRedirect(route('promotions.coupons.index'));

    // CompanyScope would otherwise hide companyA's row from this query too,
    // since the test is still "logged in" as adminB (companyB) at this point.
    expect(Coupon::withoutGlobalScopes()->where('code', 'DUPLICADO')->count())->toBe(2);
});

test('a coupon code rejects characters outside letters, numbers and dashes', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)->post(route('promotions.coupons.store'), [
        'code' => 'con espacio!', 'name' => 'Inválido', 'type' => 'percentage', 'value' => '10', 'status' => 'active',
    ])->assertSessionHasErrors('code');

    expect(Coupon::count())->toBe(0);
});
