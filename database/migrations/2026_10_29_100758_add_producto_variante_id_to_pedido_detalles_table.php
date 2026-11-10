<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->foreignId('producto_variante_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('producto_variantes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_variante_id');
        });
    }
};
