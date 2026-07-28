<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['venta_id', 'producto_id', 'producto', 'cantidad', 'precio_unitario', 'descuento', 'subtotal'])]
class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function productoRelacion(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
