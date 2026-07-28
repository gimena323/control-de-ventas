<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('talla_id')->after('modelo_id')->constrained('tallas')->cascadeOnDelete();
            $table->foreignId('color_id')->after('talla_id')->constrained('colores')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('talla_id');
            $table->dropConstrainedForeignId('color_id');
        });
    }
};
