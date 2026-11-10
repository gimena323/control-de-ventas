<?php

use App\Models\Cliente;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

beforeEach(function () {
    $this->seed(PermissionsSeeder::class);
});

function fakeGoogleUser(array $attributes = []): GoogleUser
{
    $attributes = array_merge([
        'id' => 'google-123',
        'name' => 'María Cliente',
        'email' => 'maria@gmail.com',
        'email_verified' => true,
        'verified_email' => true,
    ], $attributes);

    return GoogleUser::fake($attributes)->setRaw($attributes);
}

test('el botón de google redirige al proveedor', function () {
    config()->set('services.google.client_id', 'web-client-id');
    config()->set('services.google.client_secret', 'web-client-secret');
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect('https://socialite.fake/google/authorize');
});

test('el acceso google informa cuando falta el secreto web', function () {
    config()->set('services.google.client_id', 'web-client-id');
    config()->set('services.google.client_secret');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');
});

test('google crea una clienta verificada e inicia sesión', function () {
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('catalogo'));

    $user = User::query()->where('email', 'maria@gmail.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->google_id)->toBe('google-123')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->status)->toBe('activo')
        ->and($user->hasRole(Role::CLIENTE))->toBeTrue();

    $cliente = Cliente::query()->where('user_id', $user->id)->firstOrFail();

    expect($cliente->nombre)->toBe('María')
        ->and($cliente->apellido)->toBe('Cliente')
        ->and($cliente->email)->toBe('maria@gmail.com');
});

test('google vincula una clienta existente sin duplicarla', function () {
    $user = User::factory()->create([
        'email' => 'maria@gmail.com',
        'role' => Role::CLIENTE,
        'google_id' => null,
    ]);
    $user->assignRole(Role::CLIENTE);
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('catalogo'));

    expect(User::query()->where('email', 'maria@gmail.com')->count())->toBe(1)
        ->and($user->fresh()->google_id)->toBe('google-123')
        ->and(Cliente::query()->where('email', 'maria@gmail.com')->count())->toBe(1);
});

test('google vincula la ficha manual sin reemplazar sus datos comerciales', function () {
    $user = User::factory()->create([
        'name' => 'María Cliente',
        'email' => 'maria@gmail.com',
        'role' => Role::CLIENTE,
    ]);
    $user->assignRole(Role::CLIENTE);
    $cliente = Cliente::query()->create([
        'nombre' => 'María Manual',
        'email' => 'maria@gmail.com',
        'ci_nit' => '1234567',
        'telefono' => '70000000',
    ]);
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('catalogo'));

    expect($cliente->fresh()->user_id)->toBe($user->id)
        ->and($cliente->fresh()->nombre)->toBe('María Manual')
        ->and($cliente->fresh()->ci_nit)->toBe('1234567')
        ->and($cliente->fresh()->telefono)->toBe('70000000');
});

test('google no permite apropiarse de una cuenta del personal', function () {
    $admin = User::factory()->create([
        'email' => 'maria@gmail.com',
        'role' => Role::ADMIN,
    ]);
    $admin->assignRole(Role::ADMIN);
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
    expect($admin->fresh()->google_id)->toBeNull();
});

test('google rechaza clientas desactivadas', function () {
    $user = User::factory()->create([
        'email' => 'maria@gmail.com',
        'role' => Role::CLIENTE,
        'status' => 'inactivo',
    ]);
    $user->assignRole(Role::CLIENTE);
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});

test('google exige un correo verificado', function () {
    Socialite::fake('google', fakeGoogleUser([
        'email_verified' => false,
        'verified_email' => false,
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('google');

    $this->assertGuest();
});
