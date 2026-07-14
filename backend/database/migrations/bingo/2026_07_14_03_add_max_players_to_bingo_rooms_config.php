<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite definir, opcionalmente, un cupo máximo de jugadores para una sala.
 * BingoRoomCapacityGuard::assertHasCapacity() ya leía $room->config->max_players
 * desde antes de esta migración, pero la columna nunca existió en el módulo
 * Bingo (solo en Trivia) — el guard era un no-op permanente porque siempre
 * recibía null. Si es null, no aplica ningún límite de cupo (mismo criterio
 * que min_cards_to_start).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bingo_rooms_config', 'max_players')) {
            Schema::table('bingo_rooms_config', function (Blueprint $table) {
                $table->unsignedInteger('max_players')
                    ->nullable()
                    ->after('min_cards_to_start')
                    ->comment('Cupo máximo de jugadores en la sala (null = sin límite)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bingo_rooms_config', 'max_players')) {
            Schema::table('bingo_rooms_config', function (Blueprint $table) {
                $table->dropColumn('max_players');
            });
        }
    }
};
