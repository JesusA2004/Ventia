<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function promotionAdminFixture(): array
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $cashier = User::factory()->create(['company_id' => $company->id]);
    $cashier->assignRole('Cajero');

    return compact('company', 'admin', 'cashier');
}

test('an admin can create a promotion scoped to specific branches and categories', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['company' => $company, 'admin' => $admin] = promotionAdminFixture();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $category = Category::factory()->create(['company_id' => $company->id]);

    $this->actingAs($admin)->post(route('promotions.promotions.store'), [
        'name' => '10% Bebidas', 'type' => 'percentage', 'value' => '10', 'status' => 'active',
        'branch_ids' => [$branch->id], 'category_ids' => [$category->id],
    ])->assertRedirect(route('promotions.promotions.index'));

    $promotion = Promotion::firstOrFail();
    expect($promotion->name)->toBe('10% Bebidas')
        ->and($promotion->branches->pluck('id')->all())->toBe([$branch->id])
        ->and($promotion->categories->pluck('id')->all())->toBe([$category->id]);
});

test('a percentage promotion cannot exceed 100', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['admin' => $admin] = promotionAdminFixture();

    $this->actingAs($admin)->post(route('promotions.promotions.store'), [
        'name' => 'Imposible', 'type' => 'percentage', 'value' => '150', 'status' => 'active',
    ])->assertSessionHasErrors('value');

    expect(Promotion::count())->toBe(0);
});

test('a cashier cannot manage promotions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['cashier' => $cashier] = promotionAdminFixture();

    $this->actingAs($cashier)->get(route('promotions.promotions.index'))->assertForbidden();
});

test('an admin cannot edit a promotion belonging to another company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['admin' => $admin] = promotionAdminFixture();
    $foreignPromotion = Promotion::factory()->create();

    // CompanyScope hides the row from route-model binding for a different
    // company entirely (404) — see the equivalent StockCount test note.
    $this->actingAs($admin)->get(route('promotions.promotions.edit', $foreignPromotion))->assertNotFound();
});

test('a promotion cannot reference a product from another company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    ['admin' => $admin] = promotionAdminFixture();
    $foreignProduct = Product::factory()->create();

    $this->actingAs($admin)->post(route('promotions.promotions.store'), [
        'name' => 'Trampa', 'type' => 'percentage', 'value' => '10', 'status' => 'active',
        'product_ids' => [$foreignProduct->id],
    ])->assertSessionHasErrors('product_ids.0');

    expect(Promotion::count())->toBe(0);
});
