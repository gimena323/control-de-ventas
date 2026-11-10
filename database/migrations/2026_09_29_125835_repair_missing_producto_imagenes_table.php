<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('producto_imagenes')) {
            return;
        }

        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('ruta');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['producto_id', 'orden']);
        });

        DB::table('productos')
            ->whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->orderBy('id')
            ->eachById(function (object $producto): void {
                DB::table('producto_imagenes')->insert([
                    'producto_id' => $producto->id,
                    'ruta' => $producto->imagen,
                    'orden' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
