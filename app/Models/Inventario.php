<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['producto_id', 'stock_actual', 'disponible'])]
class Inventario extends Model
{
    protected $table = 'inventario';

    protected $appends = ['total_entradas', 'total_salidas'];

    protected function casts(): array
    {
        return [
            'stock_actual' => 'integer',
            'disponible' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class, 'inventario_id');
    }

    public function getTotalEntradasAttribute(): int
    {
        return $this->movimientos()->withExistingOrigin()->where('tipo', 'entrada')->sum('cantidad');
    }

    public function getTotalSalidasAttribute(): int
    {
        return $this->movimientos()->withExistingOrigin()->where('tipo', 'salida')->sum('cantidad');
    }
}
