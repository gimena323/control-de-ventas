<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombres', 255)->after('id')->nullable();
            $table->string('apellido_paterno', 255)->after('nombres')->nullable();
            $table->string('apellido_materno', 255)->after('apellido_paterno')->nullable();
            $table->string('telefono', 20)->after('email')->nullable();
            $table->date('fecha_ingreso')->after('telefono')->nullable();
            $table->date('fecha_fin')->after('fecha_ingreso')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nombres', 'apellido_paterno', 'apellido_materno', 'telefono', 'fecha_ingreso', 'fecha_fin']);
        });
    }
};
