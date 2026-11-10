<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_detalles', function (Blueprint $table): void {
            $table->foreignId('producto_variante_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('producto_variantes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compra_detalles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('producto_variante_id');
        });
    }
};
