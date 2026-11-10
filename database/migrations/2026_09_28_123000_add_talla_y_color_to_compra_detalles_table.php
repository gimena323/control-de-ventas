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
        Schema::table('compra_detalles', function (Blueprint $table) {
            $table->string('talla')->nullable()->after('producto');
            $table->string('color')->nullable()->after('talla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_detalles', function (Blueprint $table) {
            $table->dropColumn(['talla', 'color']);
        });
    }
};
