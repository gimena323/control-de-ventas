<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['producto_id', 'color_id', 'talla_id', 'stock', 'disponible'])]
class ProductoVariante extends Model
{
    protected $table = 'producto_variantes';

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'disponible' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function talla(): BelongsTo
    {
        return $this->belongsTo(Talla::class);
    }
}
