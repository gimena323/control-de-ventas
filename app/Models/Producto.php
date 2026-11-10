<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['categoria_id', 'marca_id', 'talla_id', 'color_id', 'nombre', 'descripcion', 'precio_venta', 'imagen', 'estado', 'genero'])]
class Producto extends Model
{
    protected $table = 'productos';

    protected function casts(): array
    {
        return [
            'precio_venta' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function talla(): BelongsTo
    {
        return $this->belongsTo(Talla::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function inventario(): HasOne
    {
        return $this->hasOne(Inventario::class);
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(ProductoVariante::class);
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class)->orderBy('orden');
    }

    public function ventasDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'producto_id');
    }

    public function comprasDetalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class, 'producto_id');
    }

    public function ultimaCompraDetalle(): HasOne
    {
        return $this->hasOne(CompraDetalle::class, 'producto_id')->latestOfMany();
    }
}
