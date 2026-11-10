<?php

namespace App\Console\Commands;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:restore-inventario-records')]
#[Description('Crea registros de inventario para todos los productos que no tengan uno')]
class CleanOrphanedInventoryData extends Command
{
    public function handle(): int
    {
        $productos = Producto::all();
        $creados = 0;

        foreach ($productos as $producto) {
            $existe = Inventario::where('producto_id', $producto->id)->exists();

            if (! $existe) {
                Inventario::create([
                    'producto_id' => $producto->id,
                    'stock_actual' => 0,
                    'disponible' => true,
                ]);
                $creados++;
            }
        }

        $this->info("Productos totales: {$productos->count()}");
        $this->info("Registros de inventario creados: {$creados}");
        $this->info('Todos los productos ahora aparecen en Inventario.');

        return self::SUCCESS;
    }
}
