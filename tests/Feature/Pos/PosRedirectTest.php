<?php

test('visiting the POS without an open cash session redirects to open a register', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['cashier'])
        ->get(route('pos.index'))
        ->assertRedirect(route('cash.sessions.create'));
});

test('the open-register screen shows an empty state when the branch has no registers configured', function () {
    $fixture = posFixture();
    $fixture['register']->delete();

    $this->actingAs($fixture['cashier'])->get(route('cash.sessions.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Cash/Open')
            ->has('registerOptions', 0));
});
