<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
            'descripcion' => fake()->sentence(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => Role::ADMIN,
            'descripcion' => 'Administrador',
        ]);
    }

    public function vendedor(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => Role::VENDEDOR,
            'descripcion' => 'Vendedor',
        ]);
    }

    public function encargadoInventario(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => Role::ENCARGADO_INVENTARIO,
            'descripcion' => 'Encargado de inventario',
        ]);
    }
}
