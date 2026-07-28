<?php

namespace Database\Factories;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Compra>
 */
class CompraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proveedor_id' => Proveedor::factory(),
            'user_id' => User::factory(),
            'numero_compra' => 'CMP-'.fake()->unique()->numerify('########'),
            'total' => fake()->randomFloat(2, 50, 5000),
            'fecha' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    public function withDetalles(int $count = 3): static
    {
        return $this->afterCreating(function (Compra $compra) use ($count) {
            CompraDetalle::factory()->count($count)->create([
                'compra_id' => $compra->id,
            ]);

            $compra->total = $compra->detalles()->sum('subtotal');
            $compra->save();
        });
    }
}
