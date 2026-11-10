<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['modelo_id']);
            $table->dropColumn('modelo_id');
        });

        Schema::dropIfExists('modelos');
    }

    public function down(): void
    {
        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['nombre', 'marca_id']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('modelo_id')->nullable()->constrained('modelos')->cascadeOnDelete();
        });
    }
};
