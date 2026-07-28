<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('estado', 20)->default('completada')->after('observaciones');
            $table->timestamp('anulada_en')->nullable()->after('estado');
            $table->foreignId('anulada_por_user_id')->nullable()->constrained('users')->nullOnDelete()->after('anulada_en');
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete()->after('venta_id');
            $table->decimal('descuento', 10, 2)->default(0)->after('precio_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['estado', 'anulada_en', 'anulada_por_user_id']);
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'descuento']);
        });
    }
};
