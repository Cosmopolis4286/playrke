<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            // Indica que el jugador eligió sus propios números en lugar de recibir
            // un cartón generado aleatoriamente. Útil para reportes, analíticas y
            // auditoría (detectar si un jugador siempre gana con cartones personalizados).
            $table->boolean('is_custom_card')->default(false)->after('is_validated');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_cards', function (Blueprint $table) {
            $table->dropColumn('is_custom_card');
        });
    }
};
