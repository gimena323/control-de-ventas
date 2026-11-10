<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tallas')->insertOrIgnore([
            ['nombre' => 'M'],
            ['nombre' => 'XL'],
        ]);
    }

    public function down(): void
    {
        DB::table('tallas')->whereIn('nombre', ['M', 'XL'])->delete();
    }
};
