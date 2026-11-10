<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pedido_id', 'producto_id', 'producto_variante_id', 'producto', 'cantidad', 'precio', 'subtotal', 'color', 'talla', 'imagen'])]
class PedidoDetalle extends Model
{
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}
