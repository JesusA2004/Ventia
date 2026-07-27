<?php

use App\Actions\Cash\OpenCashSessionAction;
use App\Actions\Inventory\AdjustStockAction;
use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Shared POS fixture: a company with a branch/warehouse/register, an admin
 * and a cashier (both properly permissioned via RolesAndPermissionsSeeder),
 * the auto-created "Público general" customer, cash + card payment methods,
 * and one simple product with 100 units in stock.
 *
 * @return array{
 *     company: Company, branch: Branch, warehouse: Warehouse,
 *     register: CashRegister, admin: User, cashier: User,
 *     customer: Customer, cash: PaymentMethod, card: PaymentMethod,
 *     product: Product, unit: Unit,
 * }
 */
function posFixture(): array
{
    (new RolesAndPermissionsSeeder)->run();

    $company = Company::factory()->create();
    $branch = Branch::factory()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id]);
    $register = CashRegister::factory()->create(['branch_id' => $branch->id, 'company_id' => $company->id, 'warehouse_id' => $warehouse->id]);

    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');
    $admin->branches()->sync([$branch->id]);

    $cashier = User::factory()->create(['company_id' => $company->id]);
    $cashier->assignRole('Cajero');
    $cashier->branches()->sync([$branch->id]);

    $customer = Customer::where('company_id', $company->id)->where('customer_type', 'general_public')->firstOrFail();

    $cash = PaymentMethod::factory()->create([
        'company_id' => $company->id, 'name' => 'Efectivo', 'code' => 'CASH', 'type' => 'cash',
        'affects_cash' => true, 'allows_change' => true, 'opens_cash_drawer' => true,
    ]);
    $card = PaymentMethod::factory()->create([
        'company_id' => $company->id, 'name' => 'Tarjeta', 'code' => 'CARD', 'type' => 'card_debit',
        'affects_cash' => false, 'allows_change' => false, 'requires_reference' => true,
    ]);

    $unit = Unit::withoutGlobalScopes()->where('symbol', 'PZA')->first()
        ?? Unit::factory()->create(['company_id' => null, 'allows_fraction' => false]);

    $product = Product::factory()->create([
        'company_id' => $company->id,
        'unit_id' => $unit->id,
        'cost' => '10.0000',
        'sale_price' => '20.0000',
        'minimum_price' => '15.0000',
        'status' => 'active',
        'visible_in_pos' => true,
        'tracking_type' => 'simple',
    ]);

    app(AdjustStockAction::class)->execute(
        $warehouse, $product, null, null,
        InventoryMovementType::Initial, '100', 'Inicial', null, $admin,
    );

    return compact('company', 'branch', 'warehouse', 'register', 'admin', 'cashier', 'customer', 'cash', 'card', 'product', 'unit');
}

/**
 * Opens a cash session for the given user via the real Action (not a raw
 * factory insert), so tests exercise the same code path production uses.
 */
function openPosSession(CashRegister $register, User $user, string $openingAmount = '500'): CashSession
{
    return app(OpenCashSessionAction::class)->execute($register, $user, $openingAmount, null);
}
