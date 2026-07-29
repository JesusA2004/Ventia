<?php

test('a 404 renders the branded Error page when debug mode is off', function () {
    config(['app.debug' => false]);

    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 404));
});

test('a 403 renders the branded Error page when debug mode is off', function () {
    config(['app.debug' => false]);
    $fixture = posFixture();

    $this->actingAs($fixture['cashier'])
        ->get(route('users.index'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 403));
});

test('debug mode still shows the default error handler instead of the branded page', function () {
    config(['app.debug' => true]);

    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertDontSee('Página no encontrada');
});
