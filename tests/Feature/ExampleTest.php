<?php

test('the root route redirects instead of rendering a page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect();
});
