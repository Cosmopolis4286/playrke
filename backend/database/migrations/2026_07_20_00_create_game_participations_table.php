<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro mínimo cross-game de "partida jugada / ganada" por usuario.
 *
 * Bingo y Trivia guardan su propio detalle de partida en tablas y (en el caso
 * de Trivia) hasta en una conexión de base de datos distinta — no hay hoy una
 * fuente única para "cuántas partidas jugó" y "cuántas ganó" un usuario que
 * sirva para el sistema de logros (ver AchievementService). Esta tabla no
 * reemplaza esos registros detallados; solo agrega el mínimo necesario para
 * alimentar GameCompletionService/AchievementService de forma unificada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_participations')) {
            return;
        }

        Schema::create('game_participations', function (Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('user_id')->constrained('users')->onDelete('cascade');
            $table->string('game', 20); // 'bingo' | 'trivia'
            $table->string('room_id')->nullable();
            $table->boolean('won')->default(false);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'game']);
            $table->index(['user_id', 'won']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_participations');
    }
};
