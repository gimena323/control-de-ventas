<?php

use App\Models\User;
use Database\Seeders\PermissionsSeeder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->seed(PermissionsSeeder::class);
    $user = User::factory()->create(['role' => 'cajera']);
    $user->assignRole('cajera');

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});
