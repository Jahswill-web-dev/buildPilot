<?php

use Inertia\Testing\AssertableInertia;

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Landing'));
});
