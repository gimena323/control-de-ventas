<?php

namespace App\Actions\Sales;

use App\Actions\Auth\SyncGoogleClientProfile;
use App\Actions\Inventory\RecordInventoryMovement;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;

class CreateSaleFromOrder
{
    public function __construct(
        private RecordInventoryMovement $recordInventoryMovement,
        private SyncGoogleClientProfile $syncClientProfile,
    ) {}

    public function execute(Pedido $pedido): Venta
    {
        $pedido = Pedido::query()
            ->with(['user', 'detalles'])
            ->lockForUpdate()
            ->findOrFail($pedido->id);

        if ($pedido->venta_id) {
            return Venta::findOrFail($pedido->venta_id);
        }

        $cliente = $this->syncClientProfile->execute($pedido->user);
        $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => $pedido->user_id,
            'numero_venta' => $this->nextSaleNumber(),
            'total' => 0,
            'fecha' => now()->toDateString(),
            'observaciones' => "Venta automática del pedido {$pedido->numero} confirmada desde solicitud de compra.",
            'estado' => 'completada',
        ]);

        $total = 0.0;

        foreach ($pedido->detalles as $detalle) {
            $producto = Producto::query()->findOrFail($detalle->producto_id);
            $variante = $this->findVariant($producto, $detalle->producto_variante_id, $detalle->talla, $detalle->color);
            $cantidad = (int) $detalle->cantidad;

            if ($variante && (! $variante->disponible || $variante->stock < $cantidad)) {
                throw ValidationException::withMessages([
                    'pedido' => "Stock insuficiente para «{$producto->nombre}» en talla {$variante->talla?->nombre} y color {$variante->color?->nombre}. Disponible: {$variante->stock}.",
                ]);
            }

            $precio = (float) $producto->precio_venta;
            $subtotal = $precio * $cantidad;

            $venta->detalles()->create([
                'producto_id' => $producto->id,
                'producto_variante_id' => $variante?->id,
                'producto' => $producto->nombre,
                'talla' => $variante?->talla?->nombre ?? $detalle->talla,
                'color' => $variante?->color?->nombre ?? $detalle->color,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'descuento' => 0,
                'subtotal' => $subtotal,
            ]);

            $this->recordInventoryMovement->salida(
                $producto,
                $cantidad,
                'venta',
                $venta->id,
                $pedido->user_id,
                "Venta automática #{$venta->numero_venta} del pedido #{$pedido->numero}",
            );

            if ($variante) {
                $variante->decrement('stock', $cantidad);
                $variante->update(['disponible' => $variante->fresh()->stock > 0]);
            }

            $detalle->update([
                'producto' => $producto->nombre,
                'precio' => $precio,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        $venta->update(['total' => $total]);
        $pedido->update([
            'venta_id' => $venta->id,
            'total' => $total,
            'estado' => Pedido::ESTADO_CONFIRMADO,
        ]);

        return $venta->fresh('detalles');
    }

    private function findVariant(Producto $producto, ?int $varianteId, ?string $talla, ?string $color): ?ProductoVariante
    {
        $hasVariants = ProductoVariante::query()
            ->where('producto_id', $producto->id)
            ->exists();

        if (! $hasVariants) {
            return null;
        }

        if ($varianteId) {
            $variante = ProductoVariante::query()
                ->with(['talla:id,nombre', 'color:id,nombre'])
                ->whereKey($varianteId)
                ->where('producto_id', $producto->id)
                ->lockForUpdate()
                ->first();

            if (! $variante) {
                throw ValidationException::withMessages([
                    'pedido' => "La talla y el color seleccionados no pertenecen a «{$producto->nombre}».",
                ]);
            }

            return $variante;
        }

        $variante = ProductoVariante::query()
            ->with(['talla:id,nombre', 'color:id,nombre'])
            ->where('producto_id', $producto->id)
            ->whereHas('talla', fn ($query) => $query->where('nombre', $talla))
            ->whereHas('color', fn ($query) => $query->where('nombre', $color))
            ->lockForUpdate()
            ->first();

        if (! $variante) {
            throw ValidationException::withMessages([
                'pedido' => "No se encontró la talla {$talla} y el color {$color} para «{$producto->nombre}».",
            ]);
        }

        return $variante;
    }

    private function nextSaleNumber(): string
    {
        return 'VNT-'.now()->format('Ymd').'-'.str_pad((Venta::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
    }
}
