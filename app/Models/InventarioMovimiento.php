<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventario_id', 'tipo', 'cantidad', 'motivo', 'user_id'])]
class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
