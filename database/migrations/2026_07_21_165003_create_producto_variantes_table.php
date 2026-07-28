<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained('colores')->cascadeOnDelete();
            $table->foreignId('talla_id')->constrained('tallas')->cascadeOnDelete();
            $table->integer('stock')->default(0);
            $table->boolean('disponible')->default(true);
            $table->timestamps();

            $table->unique(['producto_id', 'color_id', 'talla_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_variantes');
    }
};
