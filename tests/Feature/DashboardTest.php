<?php

use App\Models\User;

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
