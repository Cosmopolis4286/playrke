<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            // True cuando al menos uno de los cartones del lote fue personalizado por el jugador.
            // Permite filtrar/reportar lotes con selección manual sin hacer JOIN a bingo_cards.
            $table->boolean('has_custom_cards')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_card_lots', function (Blueprint $table) {
            $table->dropColumn('has_custom_cards');
        });
    }
};
