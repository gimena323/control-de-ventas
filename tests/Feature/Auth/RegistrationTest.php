<?php

use Laravel\Fortify\Features;

test('registration screen returns 404 when registration is disabled', function () {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is disabled.');
    }

    $response = $this->get(route('register'));

    $response->assertNotFound();
});

test('registration store returns 404 when registration is disabled', function () {
    if (! Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is disabled.');
    }

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
});
