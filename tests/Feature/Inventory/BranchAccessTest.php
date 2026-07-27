<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

test('a user restricted to one branch cannot register a stock adjustment in another branch of the same company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $ownBranch = Branch::factory()->create(['company_id' => $company->id]);
    $otherBranch = Branch::factory()->create(['company_id' => $company->id]);
    $otherWarehouse = Warehouse::factory()->create(['branch_id' => $otherBranch->id, 'company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);

    $encargado = User::factory()->create(['company_id' => $company->id]);
    $encargado->assignRole('Encargado de sucursal');
    $encargado->branches()->sync([$ownBranch->id]);

    $this->actingAs($encargado)->post(route('inventory.adjustments.store'), [
        'warehouse_id' => $otherWarehouse->id,
        'product_id' => $product->id,
        'movement_type' => 'adjustment_in',
        'quantity' => '5',
        'reason' => 'No debería permitirse',
    ])->assertForbidden();
});

test('a user with branches.access-all can operate on any branch of their company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id, 'unit_id' => $unit->id]);

    $manager = User::factory()->create(['company_id' => $company->id]);
    $manager->assignRole('Gerente');

    $this->actingAs($manager)->post(route('inventory.adjustments.store'), [
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'movement_type' => 'adjustment_in',
        'quantity' => '5',
        'reason' => 'Gerente con acceso total',
    ])->assertRedirect();

    expect(InventoryMovement::count())->toBe(1);
});
