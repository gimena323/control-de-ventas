<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'nombre', 'apellido', 'email', 'telefono', 'direccion', 'ci_nit', 'estado', 'registrado_por_user_id'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }
}
