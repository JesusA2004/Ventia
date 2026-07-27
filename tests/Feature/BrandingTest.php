<?php

test('the favicon and manifest assets exist', function () {
    expect(public_path('favicon.svg'))->toBeFile();
    expect(public_path('favicon.ico'))->toBeFile();
    expect(public_path('apple-touch-icon.png'))->toBeFile();
    expect(public_path('site.webmanifest'))->toBeFile();
    expect(public_path('icons/icon-192.png'))->toBeFile();
    expect(public_path('icons/icon-512.png'))->toBeFile();
});

test('the web manifest declares the Ventia brand', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

    expect($manifest['name'])->toBe('Ventia')
        ->and($manifest['short_name'])->toBe('Ventia')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest)->toHaveKey('theme_color')
        ->and($manifest)->toHaveKey('background_color');
});

test('the application locale is Spanish', function () {
    expect(app()->getLocale())->toBe('es');
    expect(config('app.fallback_locale'))->toBe('es');
});

test('the login page renders with the Ventia app name and no starter-kit branding', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('Ventia');
    $response->assertDontSee("Let's get started", false);
    $response->assertDontSee('Laracasts', false);
    $response->assertDontSee('Create an account', false);
    $response->assertDontSee('Sign up', false);
});
