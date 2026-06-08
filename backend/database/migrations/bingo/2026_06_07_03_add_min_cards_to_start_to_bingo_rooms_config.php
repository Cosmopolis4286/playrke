<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite definir, opcionalmente, una cantidad mínima de cartones vendidos
     * que una sala debe alcanzar antes de que su sorteo pueda iniciar — tanto
     * de forma autónoma (StartPendingBingoDrawsCommand) como manual (activateNow
     * / scheduleStart). Si es null, no aplica ninguna condición de mínimo.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('bingo_rooms_config', 'min_cards_to_start')) {
            Schema::table('bingo_rooms_config', function (Blueprint $table) {
                $table->unsignedInteger('min_cards_to_start')
                    ->nullable()
                    ->after('start_mode')
                    ->comment('Cantidad mínima de cartones vendidos requerida para iniciar el sorteo (null = sin condición)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bingo_rooms_config', 'min_cards_to_start')) {
            Schema::table('bingo_rooms_config', function (Blueprint $table) {
                $table->dropColumn('min_cards_to_start');
            });
        }
    }
};
