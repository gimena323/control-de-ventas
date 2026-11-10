<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table): void {
            $table->foreignId('producto_variante_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('producto_variantes')
                ->nullOnDelete();
            $table->string('talla')->nullable()->after('producto');
            $table->string('color')->nullable()->after('talla');
        });
    }

    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('producto_variante_id');
            $table->dropColumn(['talla', 'color']);
        });
    }
};
