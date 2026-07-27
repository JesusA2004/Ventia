<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function makeCatalogAdmin(): array
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    return [$company, $admin];
}

test('categories are isolated between companies', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin] = makeCatalogAdmin();

    $otherCompanyCategory = Category::factory()->create();

    $this->actingAs($admin)
        ->get(route('catalog.categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('categories.data', []));

    $this->actingAs($admin)
        ->get(route('catalog.categories.edit', $otherCompanyCategory))
        ->assertNotFound();
});

test('category name must be unique within the same parent and company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin] = makeCatalogAdmin();

    Category::factory()->create(['company_id' => $company->id, 'name' => 'Bebidas', 'parent_id' => null]);

    $response = $this->actingAs($admin)->post(route('catalog.categories.store'), [
        'name' => 'Bebidas',
        'parent_id' => null,
        'sort_order' => 0,
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('name');
    expect(Category::where('company_id', $company->id)->where('name', 'Bebidas')->count())->toBe(1);
});

test('a category cannot be deleted while it has products', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin] = makeCatalogAdmin();

    $category = Category::factory()->create(['company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    Product::factory()->create(['company_id' => $company->id, 'category_id' => $category->id, 'unit_id' => $unit->id]);

    $this->actingAs($admin)
        ->delete(route('catalog.categories.destroy', $category))
        ->assertSessionHasErrors();

    expect(Category::find($category->id))->not->toBeNull();
});

test('assigning a category from another company as parent is rejected', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    [$company, $admin] = makeCatalogAdmin();

    $foreignCategory = Category::factory()->create();

    $response = $this->actingAs($admin)->post(route('catalog.categories.store'), [
        'name' => 'Nueva',
        'parent_id' => $foreignCategory->id,
        'sort_order' => 0,
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('parent_id');
});
