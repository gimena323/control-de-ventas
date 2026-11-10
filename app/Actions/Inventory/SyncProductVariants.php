<?php

namespace App\Actions\Inventory;

use App\Models\Color;
use App\Models\CompraDetalle;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Talla;
use App\Models\VentaDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SyncProductVariants
{
    /**
     * @param  array<int, int|string>  $tallaIds
     * @param  array<int, int|string>  $colorIds
     */
    public function execute(Producto $producto, array $tallaIds, array $colorIds): void
    {
        DB::transaction(function () use ($producto, $tallaIds, $colorIds): void {
            $idsConservados = collect();

            foreach (array_unique(array_map('intval', $tallaIds)) as $tallaId) {
                foreach (array_unique(array_map('intval', $colorIds)) as $colorId) {
                    $variante = ProductoVariante::firstOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'talla_id' => $tallaId,
                            'color_id' => $colorId,
                        ],
                        ['stock' => 0, 'disponible' => false],
                    );

                    $idsConservados->push($variante->id);
                }
            }

            $idsConservados->push(...$this->restoreHistoricalStock($producto)->modelKeys());

            $producto->variantes()->get()->each(function (ProductoVariante $variante): void {
                $debeEstarDisponible = $variante->stock > 0;

                if ($variante->disponible !== $debeEstarDisponible) {
                    $variante->update(['disponible' => $debeEstarDisponible]);
                }
            });

            $producto->variantes()
                ->whereNotIn('id', $idsConservados->unique()->all())
                ->where('stock', '<=', 0)
                ->whereDoesntHave('compraDetalles')
                ->whereDoesntHave('ventaDetalles')
                ->delete();

            $stockTotal = (int) $producto->variantes()->sum('stock');

            Inventario::updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => $stockTotal,
                    'disponible' => $stockTotal > 0,
                ],
            );
        }, 3);
    }

    /** @return Collection<int, ProductoVariante> */
    private function restoreHistoricalStock(Producto $producto): Collection
    {
        $compras = CompraDetalle::query()
            ->where('producto_id', $producto->id)
            ->whereNotNull('talla')
            ->whereNotNull('color')
            ->whereHas('compra', fn ($query) => $query->where('estado', '!=', 'anulada'))
            ->selectRaw('talla, color, SUM(cantidad) as cantidad_total')
            ->groupBy('talla', 'color')
            ->get()
            ->keyBy(fn (CompraDetalle $detalle): string => $this->variantKey($detalle->talla, $detalle->color));

        $ventas = VentaDetalle::query()
            ->where('producto_id', $producto->id)
            ->whereNotNull('talla')
            ->whereNotNull('color')
            ->whereHas('venta', fn ($query) => $query->where('estado', '!=', 'anulada'))
            ->selectRaw('talla, color, SUM(cantidad) as cantidad_total')
            ->groupBy('talla', 'color')
            ->get()
            ->keyBy(fn (VentaDetalle $detalle): string => $this->variantKey($detalle->talla, $detalle->color));

        $tallas = Talla::query()->get()->keyBy(fn (Talla $talla): string => mb_strtolower(trim($talla->nombre)));
        $colores = Color::query()->get()->keyBy(fn (Color $color): string => mb_strtolower(trim($color->nombre)));
        $variantes = new Collection;

        foreach ($compras->keys()->merge($ventas->keys())->unique() as $key) {
            $compra = $compras->get($key);
            $venta = $ventas->get($key);
            $tallaNombre = $compra?->talla ?? $venta?->talla;
            $colorNombre = $compra?->color ?? $venta?->color;
            $talla = $tallas->get(mb_strtolower(trim((string) $tallaNombre)));
            $color = $colores->get(mb_strtolower(trim((string) $colorNombre)));

            if (! $talla || ! $color) {
                continue;
            }

            $stock = max(0, (int) ($compra?->cantidad_total ?? 0) - (int) ($venta?->cantidad_total ?? 0));
            $variante = ProductoVariante::updateOrCreate(
                [
                    'producto_id' => $producto->id,
                    'talla_id' => $talla->id,
                    'color_id' => $color->id,
                ],
                ['stock' => $stock, 'disponible' => $stock > 0],
            );

            CompraDetalle::query()
                ->where('producto_id', $producto->id)
                ->whereNull('producto_variante_id')
                ->where('talla', $tallaNombre)
                ->where('color', $colorNombre)
                ->update(['producto_variante_id' => $variante->id]);

            VentaDetalle::query()
                ->where('producto_id', $producto->id)
                ->whereNull('producto_variante_id')
                ->where('talla', $tallaNombre)
                ->where('color', $colorNombre)
                ->update(['producto_variante_id' => $variante->id]);

            $variantes->push($variante);
        }

        return $variantes;
    }

    private function variantKey(string $talla, string $color): string
    {
        return mb_strtolower(trim($talla)).'|'.mb_strtolower(trim($color));
    }
}
