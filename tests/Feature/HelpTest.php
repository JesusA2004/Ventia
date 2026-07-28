<?php

test('the getting started checklist marks steps completed once real data exists', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('help.getting-started'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Help/GettingStarted')
            ->where('steps.1.completed', true) // branch created by posFixture()
            ->where('steps.6.completed', true) // product created by posFixture()
            ->where('steps.9.completed', false)); // no completed sale yet
});

test('the guide page renders', function () {
    $fixture = posFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('help.guide'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Help/Guide'));
});
