<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('producto');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('color')->nullable();
            $table->string('talla')->nullable();
            $table->string('imagen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};
