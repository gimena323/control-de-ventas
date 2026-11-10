<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventario_id', 'tipo', 'cantidad', 'motivo', 'origen_tipo', 'origen_id', 'user_id'])]
class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'origen_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithExistingOrigin(Builder $query): Builder
    {
        return $query->where(function (Builder $originQuery): void {
            $originQuery
                ->where(function (Builder $purchaseQuery): void {
                    $purchaseQuery
                        ->where('origen_tipo', 'compra')
                        ->whereExists(function ($query): void {
                            $query->selectRaw('1')
                                ->from('compras')
                                ->whereColumn('compras.id', 'inventario_movimientos.origen_id');
                        });
                })
                ->orWhere(function (Builder $saleQuery): void {
                    $saleQuery
                        ->where('origen_tipo', 'venta')
                        ->whereExists(function ($query): void {
                            $query->selectRaw('1')
                                ->from('ventas')
                                ->whereColumn('ventas.id', 'inventario_movimientos.origen_id')
                                ->where('ventas.estado', '!=', 'anulada');
                        });
                })
                ->orWhere(function (Builder $orderQuery): void {
                    $orderQuery
                        ->where('origen_tipo', 'pedido')
                        ->whereExists(function ($query): void {
                            $query->selectRaw('1')
                                ->from('pedidos')
                                ->whereColumn('pedidos.id', 'inventario_movimientos.origen_id')
                                ->where('pedidos.estado', '!=', Pedido::ESTADO_PENDIENTE)
                                ->where('pedidos.estado', '!=', Pedido::ESTADO_CANCELADO);
                        });
                })
                ->orWhereNotIn('origen_tipo', ['compra', 'venta', 'anulacion_venta', 'pedido']);
        });
    }
}
