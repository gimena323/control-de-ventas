<?php

namespace App\Models;

use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nombre', 'ci_nit', 'telefono', 'direccion', 'email'])]
class Proveedor extends Model
{
    protected $table = 'proveedores';

    /** @use HasFactory<ProveedorFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }
}
