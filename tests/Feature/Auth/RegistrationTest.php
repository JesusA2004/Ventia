<?php

use App\Models\User;

test('the registration route does not exist', function () {
    $this->get('/register')->assertNotFound();
});

test('a user cannot be created via a POST to the registration endpoint', function () {
    $usersBefore = User::count();

    $response = $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
    expect(User::count())->toBe($usersBefore);
    expect(User::where('email', 'intruso@example.com')->exists())->toBeFalse();
});
