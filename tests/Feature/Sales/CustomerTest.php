<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\ProductPrice;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

test('a company automatically gets a Público general customer when created', function () {
    $company = Company::factory()->create();

    $general = Customer::where('company_id', $company->id)->where('customer_type', 'general_public')->first();

    expect($general)->not->toBeNull()
        ->and($general->name)->toBe('Público general');
});

test('customers are isolated between companies', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Cliente A']);
    Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Cliente B']);

    $adminB = User::factory()->create(['company_id' => $companyB->id]);
    $adminB->assignRole('Administrador de empresa');

    $this->actingAs($adminB)
        ->get(route('customers.index'))
        ->assertInertia(fn ($page) => $page->has('customers.data', 2)); // Cliente B + auto general_public
});

test('the general public customer cannot be deleted', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('Administrador de empresa');

    $general = Customer::where('company_id', $company->id)->where('customer_type', 'general_public')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('customers.destroy', $general))
        ->assertSessionHasErrors();

    expect(Customer::find($general->id))->not->toBeNull();
});

test('a customer with a price list gets its override price resolved at checkout', function () {
    $fixture = posFixture();

    $priceList = PriceList::factory()->create(['company_id' => $fixture['company']->id]);
    ProductPrice::create([
        'company_id' => $fixture['company']->id,
        'product_id' => $fixture['product']->id,
        'price_list_id' => $priceList->id,
        'price' => '17.0000',
        'status' => 'active',
    ]);

    $vipCustomer = Customer::factory()->create([
        'company_id' => $fixture['company']->id,
        'price_list_id' => $priceList->id,
    ]);

    $session = openPosSession($fixture['register'], $fixture['cashier']);

    $this->actingAs($fixture['cashier'])->postJson(route('sales.store'), [
        'register_id' => $fixture['register']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $vipCustomer->id,
        'checkout_uuid' => (string) Str::uuid(),
        'items' => [['product_id' => $fixture['product']->id, 'quantity' => '2']],
        'payments' => [['payment_method_id' => $fixture['cash']->id, 'amount' => '34']],
    ])->assertCreated();

    $sale = Sale::firstOrFail();
    expect($sale->total)->toBe('34.0000'); // 2 x 17, not 2 x 20
});
