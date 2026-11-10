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
        Schema::table('compras', function (Blueprint $table) {
            $table->string('estado', 20)->default('completada')->after('observaciones');
            $table->timestamp('anulada_en')->nullable()->after('estado');
            $table->foreignId('anulada_por_user_id')
                ->nullable()
                ->after('anulada_en')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulada_por_user_id');
            $table->dropColumn(['estado', 'anulada_en']);
        });
    }
};
