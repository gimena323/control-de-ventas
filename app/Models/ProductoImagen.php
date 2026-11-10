<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['producto_id', 'color_id', 'ruta', 'orden'])]
class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';

    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
