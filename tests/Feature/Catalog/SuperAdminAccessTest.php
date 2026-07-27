<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('a superadministrator can see catalog data from every company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superadmin = User::factory()->create(['company_id' => null]);
    $superadmin->assignRole('Superadministrador');

    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    Category::factory()->create(['company_id' => $companyA->id]);
    Category::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($superadmin)
        ->get(route('catalog.categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('categories.data', 2));
});

test('a superadministrator can edit a product belonging to any company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superadmin = User::factory()->create(['company_id' => null]);
    $superadmin->assignRole('Superadministrador');

    $company = Company::factory()->create();
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);

    $this->actingAs($superadmin)
        ->get(route('products.edit', $product))
        ->assertOk();
});
