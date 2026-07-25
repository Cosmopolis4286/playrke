<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade índice a external_players.phone.
 *
 * phone es la columna de búsqueda más frecuente del dominio: la usa
 * ExternalPlayerController::check() (vendedor verificando si el comprador ya
 * existe), PlayerLinkTrait::handleExternalPlayers() (búsqueda exacta al
 * generar cartones), y ahora ExternalPlayerAdminService::search() (panel
 * admin) — la tabla nunca tuvo índice en esta columna pese a ser la más
 * consultada, solo estaba cubierta indirectamente por idx_game_room_creator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_players', function (Blueprint $table) {
            $table->index('phone', 'idx_external_players_phone');
        });
    }

    public function down(): void
    {
        Schema::table('external_players', function (Blueprint $table) {
            $table->dropIndex('idx_external_players_phone');
        });
    }
};
