<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_draws_code', function (Blueprint $table) {
            // tinyInteger (signed, max 127) overflow para variantes con >127 fichas.
            // unsignedSmallInteger soporta hasta 65 535 posiciones.
            // Los índices unique con round_id ya fueron creados por 2026_07_13_01.
            $table->unsignedSmallInteger('num')->comment('Posición en la secuencia (1-based)')->change();
        });
    }

    public function down(): void
    {
        Schema::table('bingo_draws_code', function (Blueprint $table) {
            $table->tinyInteger('num')->comment('Número (1 dígito)')->change();
        });
    }
};
