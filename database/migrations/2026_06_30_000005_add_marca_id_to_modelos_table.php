<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->foreignId('marca_id')->after('id')->constrained('marcas')->cascadeOnDelete();
            $table->dropUnique('modelos_nombre_unique');
            $table->unique(['nombre', 'marca_id']);
        });
    }

    public function down(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropColumn('marca_id');
            $table->unique('nombre');
        });
    }
};
