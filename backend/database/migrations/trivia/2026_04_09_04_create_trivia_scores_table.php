<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_scores')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('game_id');
            $table->foreign('game_id')->references('id')->on('trivia_games')->onDelete('cascade');
            $table->string('player_id'); // Referencia externa, no FK
            $table->unsignedInteger('final_score')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('wrong_answers')->default(0);
            $table->unsignedInteger('average_response_time_ms')->default(0); // Exacto, no float
            $table->unsignedInteger('position')->nullable(); // Null durante juego, valor al finalizar
            $table->unsignedInteger('max_streak')->default(0);
            $table->boolean('is_winner')->default(false); // Acceso rápido al ganador
            $table->unsignedInteger('total_questions')->default(0); // Contexto del score
            $table->unsignedInteger('total_response_time_ms')->default(0); // Para recalcular promedio
            $table->jsonb('metadata')->nullable(); // Historial detallado (jsonb para PostgreSQL)
            $table->timestamps();
            $table->softDeletes();

            // Índices optimizados
            $table->unique(['game_id', 'player_id']); // Ya cubre búsqueda por game_id + player_id
            $table->index(['game_id', 'position']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_scores');
    }
};
