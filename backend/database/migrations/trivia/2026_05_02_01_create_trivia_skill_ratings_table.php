<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skill rating por jugador — base del sistema de ligas y matchmaking.
 *
 * El rating se actualiza tras cada partida usando un algoritmo ELO simplificado
 * adaptado para trivia (no es 1v1, sino ranking posicional).
 *
 * Columnas clave:
 *  rating          Puntuación ELO actual (base 1000, rango práctico 200-3000)
 *  peak_rating     Máximo histórico — nunca baja, sirve para logros
 *  games_played    Partidas completadas (≥ 1 respuesta)
 *  wins            Veces en top 3 (o equipo ganador en modes teams/duel)
 *  win_rate        wins / games_played — denormalizado para queries rápidas
 *  avg_correct_pct Promedio de % respuestas correctas — indicador de habilidad pura
 *  avg_response_ms Promedio de tiempo de respuesta — indicador de velocidad
 *  tier            Bronze | Silver | Gold | Platinum | Diamond — recalculado periódicamente
 *  category_ratings JSONB — rating por categoría (futuro: matchmaking por categoría)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_skill_ratings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('user_id');                          // FK lógica → MySQL users.id
            $table->unsignedInteger('rating')->default(1000);
            $table->unsignedInteger('peak_rating')->default(1000);
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('wins')->default(0);       // top 3 o equipo ganador
            $table->decimal('win_rate', 5, 4)->default(0);     // 0.0000 – 1.0000
            $table->decimal('avg_correct_pct', 5, 4)->default(0);
            $table->unsignedInteger('avg_response_ms')->default(0);
            $table->string('tier', 20)->default('bronze');     // bronze|silver|gold|platinum|diamond
            $table->jsonb('category_ratings')->nullable();     // {category: rating}
            $table->timestamp('last_game_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('rating');
            $table->index('tier');
            $table->index(['tier', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_skill_ratings');
    }
};
