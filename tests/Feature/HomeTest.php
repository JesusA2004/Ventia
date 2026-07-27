<?php

use App\Models\User;

test('a guest visiting the root is redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('an authenticated user visiting the root is redirected to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
});
