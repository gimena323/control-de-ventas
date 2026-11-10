<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    protected $table = 'colores';

    protected $fillable = ['nombre', 'codigo_hex'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(ProductoVariante::class);
    }
}
