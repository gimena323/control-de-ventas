<?php

namespace Database\Factories;

use App\Models\CompraDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompraDetalle>
 */
class CompraDetalleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 20);
        $precio_unitario = fake()->randomFloat(2, 5, 200);

        return [
            'producto' => fake()->word(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $cantidad * $precio_unitario,
        ];
    }
}
