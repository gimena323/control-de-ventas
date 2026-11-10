<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('apellido', 100)->nullable()->after('nombre');
            $table->string('ci_nit', 20)->nullable()->unique()->after('apellido');
        });

        DB::table('clientes')->orderBy('id')->each(function ($cliente) {
            $ciNit = $cliente->numero_documento;
            DB::table('clientes')
                ->where('id', $cliente->id)
                ->update(['ci_nit' => $ciNit]);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_numero_documento_unique');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['tipo_documento', 'numero_documento']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('tipo_documento')->nullable()->after('direccion');
            $table->string('numero_documento')->nullable()->unique()->after('tipo_documento');
        });

        DB::table('clientes')->orderBy('id')->each(function ($cliente) {
            DB::table('clientes')
                ->where('id', $cliente->id)
                ->update([
                    'tipo_documento' => 'DNI',
                    'numero_documento' => $cliente->ci_nit,
                ]);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['apellido', 'ci_nit']);
        });
    }
};
