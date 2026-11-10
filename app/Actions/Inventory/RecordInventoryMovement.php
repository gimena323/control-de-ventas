<?php

namespace App\Actions\Inventory;

use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use Illuminate\Validation\ValidationException;

class RecordInventoryMovement
{
    public function setEntradaOrigin(
        Producto $producto,
        int $cantidad,
        string $origenTipo,
        int $origenId,
        ?int $userId,
        string $motivo,
    ): Inventario {
        $inventario = $this->lockedInventory($producto);
        $movimientos = InventarioMovimiento::query()
            ->where('inventario_id', $inventario->id)
            ->where('tipo', 'entrada')
            ->where('origen_tipo', $origenTipo)
            ->where('origen_id', $origenId)
            ->lockForUpdate()
            ->get();

        $cantidadAnterior = $movimientos->sum('cantidad');
        $diferencia = $cantidad - $cantidadAnterior;

        if ($diferencia < 0 && $inventario->stock_actual < abs($diferencia)) {
            throw ValidationException::withMessages([
                'compra' => 'No se puede reducir la compra a esa cantidad porque parte de las existencias ya salió del inventario.',
            ]);
        }

        if ($diferencia > 0) {
            $inventario->increment('stock_actual', $diferencia);
        } elseif ($diferencia < 0) {
            $inventario->decrement('stock_actual', abs($diferencia));
        }

        $this->markAsAvailableIfInStock($inventario);

        $movimientoPrincipal = $movimientos->shift();

        if ($cantidad === 0) {
            $movimientoPrincipal?->delete();
        } elseif ($movimientoPrincipal) {
            $movimientoPrincipal->update([
                'cantidad' => $cantidad,
                'motivo' => $motivo,
                'user_id' => $userId,
            ]);
        } else {
            $this->createMovement($inventario, 'entrada', $cantidad, $origenTipo, $origenId, $userId, $motivo);
        }

        $movimientos->each->delete();

        return $inventario->fresh();
    }

    public function entrada(
        Producto $producto,
        int $cantidad,
        string $origenTipo,
        int $origenId,
        ?int $userId,
        string $motivo,
    ): Inventario {
        $inventario = $this->lockedInventory($producto);
        $inventario->increment('stock_actual', $cantidad);

        $this->markAsAvailableIfInStock($inventario);

        $this->createMovement($inventario, 'entrada', $cantidad, $origenTipo, $origenId, $userId, $motivo);

        return $inventario->fresh();
    }

    public function salida(
        Producto $producto,
        int $cantidad,
        string $origenTipo,
        int $origenId,
        ?int $userId,
        string $motivo,
    ): Inventario {
        $inventario = $this->lockedInventory($producto);

        if (! $inventario->disponible) {
            throw ValidationException::withMessages([
                'detalles' => "El producto «{$producto->nombre}» no está disponible para la venta.",
            ]);
        }

        if ($inventario->stock_actual < $cantidad) {
            throw ValidationException::withMessages([
                'detalles' => "Stock insuficiente para «{$producto->nombre}». Disponible: {$inventario->stock_actual}.",
            ]);
        }

        $inventario->decrement('stock_actual', $cantidad);

        $this->createMovement($inventario, 'salida', $cantidad, $origenTipo, $origenId, $userId, $motivo);

        return $inventario->fresh();
    }

    public function anularEntrada(
        Producto $producto,
        int $cantidad,
        string $origenTipo,
        int $origenId,
        ?int $userId,
        string $motivo,
    ): Inventario {
        $inventario = $this->lockedInventory($producto);

        if ($inventario->stock_actual < $cantidad) {
            throw ValidationException::withMessages([
                'compra' => 'No se puede anular esta compra porque parte de sus productos ya fue vendida. La compra permanecerá registrada.',
            ]);
        }

        $inventario->decrement('stock_actual', $cantidad);
        $this->createMovement($inventario, 'salida', $cantidad, $origenTipo, $origenId, $userId, $motivo);

        return $inventario->fresh();
    }

    public function forgetOrigin(string $origenTipo, int $origenId): void
    {
        $movimientos = InventarioMovimiento::query()
            ->where('origen_tipo', $origenTipo)
            ->where('origen_id', $origenId)
            ->get();

        foreach ($movimientos as $movimiento) {
            $inventario = Inventario::query()->lockForUpdate()->findOrFail($movimiento->inventario_id);

            if ($movimiento->tipo === 'entrada') {
                if ($inventario->stock_actual < $movimiento->cantidad) {
                    throw ValidationException::withMessages([
                        'compra' => 'No se puede modificar o eliminar la compra porque parte de ese stock ya fue vendido.',
                    ]);
                }

                $inventario->decrement('stock_actual', $movimiento->cantidad);
            } else {
                $inventario->increment('stock_actual', $movimiento->cantidad);
            }

            $movimiento->delete();
        }
    }

    private function lockedInventory(Producto $producto): Inventario
    {
        Inventario::firstOrCreate(
            ['producto_id' => $producto->id],
            ['stock_actual' => 0, 'disponible' => true],
        );

        return Inventario::query()
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function markAsAvailableIfInStock(Inventario $inventario): void
    {
        $inventario->refresh();

        if ($inventario->stock_actual > 0 && ! $inventario->disponible) {
            $inventario->update(['disponible' => true]);
        }
    }

    private function createMovement(
        Inventario $inventario,
        string $tipo,
        int $cantidad,
        string $origenTipo,
        int $origenId,
        ?int $userId,
        string $motivo,
    ): void {
        InventarioMovimiento::create([
            'inventario_id' => $inventario->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'user_id' => $userId,
        ]);
    }
}
