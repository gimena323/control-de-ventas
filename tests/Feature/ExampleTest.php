<?php

use Inertia\Testing\AssertableInertia as Assert;

test('la portada publica muestra el componente principal y su imagen', function () {
    $response = $this->get(route('principal'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));

    expect(public_path('images/emma-fashion-hero-v2.png'))->toBeFile();
    expect(public_path('images/emma-fashion-collection-v2.png'))->toBeFile();
});
