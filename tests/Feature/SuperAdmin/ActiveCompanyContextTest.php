<?php

use App\Enums\Status;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Company;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

function superAdmin(): User
{
    (new RolesAndPermissionsSeeder)->run();

    $superAdmin = User::factory()->create(['company_id' => null]);
    $superAdmin->assignRole('Superadministrador');

    return $superAdmin;
}

test('a superadministrator without an active company is redirected to the selector instead of crashing', function () {
    $superAdmin = superAdmin();

    $this->actingAs($superAdmin)
        ->get(route('pos.index'))
        ->assertRedirect(route('company-selection.index'));
});

test('a superadministrator can select an active company and then enter the POS', function () {
    $superAdmin = superAdmin();
    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $register = CashRegister::factory()->create([
        'branch_id' => $branch->id, 'company_id' => $company->id, 'warehouse_id' => $warehouse->id,
    ]);

    $this->actingAs($superAdmin)
        ->post(route('company-selection.store'), ['company_id' => $company->id])
        ->assertRedirect(route('dashboard'));

    expect(session('active_company_id'))->toBe($company->id);

    openPosSession($register, $superAdmin);

    $this->actingAs($superAdmin)
        ->get(route('pos.index'))
        ->assertOk();
});

test('a superadministrator cannot select an inactive company', function () {
    $superAdmin = superAdmin();
    $inactive = Company::factory()->create(['status' => Status::Inactive]);

    $this->actingAs($superAdmin)
        ->post(route('company-selection.store'), ['company_id' => $inactive->id])
        ->assertSessionHasErrors('company_id');
});

test('a regular admin never sees the company selector and always acts on their own company', function () {
    (new RolesAndPermissionsSeeder)->run();

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $this->actingAs($admin)
        ->get(route('company-selection.index'))
        ->assertForbidden();
});

test('mi empresa renders for a superadministrator once a company is active, never a 404', function () {
    $superAdmin = superAdmin();
    $company = Company::factory()->create();

    $this->actingAs($superAdmin)
        ->post(route('company-selection.store'), ['company_id' => $company->id]);

    $this->actingAs($superAdmin)
        ->get(route('settings.company.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Company')
            ->where('company.id', $company->id));
});

test('mi empresa redirects a superadministrator with no active company instead of 404ing', function () {
    $superAdmin = superAdmin();

    $this->actingAs($superAdmin)
        ->get(route('settings.company.edit'))
        ->assertRedirect(route('company-selection.index'));
});

test('a superadministrator creating a product without an active company is redirected, never 500', function () {
    $superAdmin = superAdmin();
    $unit = Unit::factory()->create(['company_id' => null]);

    $this->actingAs($superAdmin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'unit_id' => $unit->id,
            'name' => 'Producto de prueba',
            'sku' => 'SKU-TEST-1',
            'product_type' => 'physical',
            'tracking_type' => 'simple',
            'cost' => '10',
            'sale_price' => '20',
            'minimum_stock' => '0',
            'status' => 'active',
        ])
        ->assertRedirect(route('company-selection.index'));
});

test('a superadministrator without an active company cannot create records with a null company_id', function () {
    $superAdmin = superAdmin();

    $this->actingAs($superAdmin)
        ->post(route('catalog.categories.store'), [
            'name' => 'Categoría huérfana',
            'status' => 'active',
        ])
        ->assertRedirect(route('company-selection.index'));

    expect(Category::withoutGlobalScopes()->where('name', 'Categoría huérfana')->exists())->toBeFalse();
});

test('records created by a superadministrator with an active company are attributed to that company, not left orphaned', function () {
    $superAdmin = superAdmin();
    $company = Company::factory()->create();

    $this->actingAs($superAdmin)
        ->post(route('company-selection.store'), ['company_id' => $company->id]);

    $this->actingAs($superAdmin)->post(route('catalog.categories.store'), [
        'name' => 'Categoría de prueba',
        'status' => 'active',
    ])->assertRedirect(route('catalog.categories.index'));

    $category = Category::withoutGlobalScopes()->where('name', 'Categoría de prueba')->firstOrFail();

    expect($category->company_id)->toBe($company->id);
});
