<?php

use App\Actions\Sales\CompleteSaleAction;
use App\Models\User;
use Illuminate\Http\Request;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard exposes granularity in filters and an expiring lots count in metrics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard', ['granularity' => 'hour']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.granularity', 'hour')
            ->has('metrics.expiring_lots_count'));
});

test('an invalid granularity value falls back to daily instead of erroring', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard', ['granularity' => 'not-a-real-value']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.granularity', 'day'));
});

/**
 * Regression coverage for a real 500: Request::date() resolves through the
 * Date facade, and this app calls Date::use(CarbonImmutable::class) in
 * AppServiceProvider, so date_from/date_to arrive as CarbonImmutable —
 * while Carbon::today() (used for every other preset) stays
 * Illuminate\Support\Carbon. Both DashboardController::resolveRange() and
 * DashboardMetricsService (bucketKey() in particular, fed by the
 * Eloquent-cast Sale::completed_at, which is also CarbonImmutable) must
 * accept either via CarbonInterface, not just Carbon.
 */
test('every dashboard preset and granularity combination renders without a 500, including a custom range with real sales in it', function () {
    $fixture = posFixture();
    $this->actingAs($fixture['cashier']);
    // ActiveCompanyContext resolves the user from the bound Request
    // instance, which a direct Action call (unlike a dispatched
    // $this->get()/post()) never passes through the auth middleware for —
    // actingAs() alone leaves it null here, so it's set explicitly too.
    app(Request::class)->setUserResolver(fn () => $fixture['cashier']);

    $session = openPosSession($fixture['register'], $fixture['cashier']);

    app(CompleteSaleAction::class)->execute([
        'branch_id' => $fixture['branch']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'register_id' => $fixture['register']->id,
        'cash_session_id' => $session->id,
        'customer_id' => $fixture['customer']->id,
        'items' => [
            ['product_id' => $fixture['product']->id, 'quantity' => '1'],
        ],
    ], [
        ['payment_method_id' => $fixture['cash']->id, 'amount' => '100'],
    ], $fixture['cashier']);

    $today = now()->toDateString();
    $lastWeek = now()->subDays(7)->toDateString();

    $this->actingAs($fixture['admin'])
        ->get(route('dashboard', ['preset' => 'today']))
        ->assertOk();

    // Repeat visits echo date_from/date_to back (as the real frontend
    // does), which is exactly the path that produced CarbonImmutable.
    $this->actingAs($fixture['admin'])
        ->get(route('dashboard', ['preset' => 'today', 'date_from' => $today, 'date_to' => $today]))
        ->assertOk();

    $this->actingAs($fixture['admin'])
        ->get(route('dashboard', ['preset' => 'custom', 'date_from' => $lastWeek, 'date_to' => $today]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.preset', 'custom'));

    foreach (['hour', 'day', 'week'] as $granularity) {
        $this->actingAs($fixture['admin'])
            ->get(route('dashboard', [
                'preset' => 'custom',
                'date_from' => $lastWeek,
                'date_to' => $today,
                'granularity' => $granularity,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.granularity', $granularity)
                ->has('metrics.sales_over_time'));
    }
});
