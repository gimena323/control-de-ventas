<?php

namespace App\Models;

use Database\Factories\CompraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['proveedor_id', 'user_id', 'numero_compra', 'total', 'fecha', 'observaciones', 'estado', 'anulada_en', 'anulada_por_user_id'])]
class Compra extends Model
{
    /** @use HasFactory<CompraFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'fecha' => 'date',
            'anulada_en' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por_user_id');
    }

    public function isAnulada(): bool
    {
        return $this->estado === 'anulada';
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CompraDetalle::class);
    }
}
