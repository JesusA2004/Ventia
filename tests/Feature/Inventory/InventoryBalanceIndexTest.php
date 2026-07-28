<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;

function balancesFixture(): array
{
    (new RolesAndPermissionsSeeder)->run();

    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => null, 'symbol' => 'PZA', 'allows_fraction' => false]);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'minimum_stock' => '10.0000',
    ]);

    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    app(AdjustStockAction::class)->execute(
        $warehouse, $product, null, null,
        InventoryMovementType::Initial, '5', 'Inicial', null, $admin,
    );

    return compact('company', 'branch', 'warehouse', 'unit', 'product', 'admin');
}

test('the balances index exposes unit, formatted-ready quantity fields and a low stock flag', function () {
    $fixture = balancesFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('inventory.balances.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Balances/Index')
            ->has('balances.data', 1)
            ->where('balances.data.0.unit_symbol', 'PZA')
            ->where('balances.data.0.unit_allows_fraction', false)
            ->where('balances.data.0.is_low_stock', true)
            ->has('balances.data.0.average_cost'));
});

test('average cost is hidden from users without the inventory.view-costs permission', function () {
    $fixture = balancesFixture();

    $supervisor = User::factory()->create(['company_id' => $fixture['company']->id]);
    $supervisor->assignRole('Supervisor');

    $this->actingAs($supervisor)
        ->get(route('inventory.balances.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('balances.data', 1)
            ->missing('balances.data.0.average_cost'));
});
