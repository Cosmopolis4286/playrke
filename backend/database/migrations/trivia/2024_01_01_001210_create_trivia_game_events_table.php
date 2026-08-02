<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_game_events')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_game_events', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('game_id', 20);
            $table->string('type', 30); // question_sent, answer_received, game_started, etc.
            $table->string('player_id')->nullable(); // Quién disparó el evento (opcional)
            $table->string('game_question_id', 20)->nullable(); // Contexto de la pregunta específica, sin FK física
            $table->string('source', 20)->default('server'); // server, client, system
            $table->jsonb('payload'); // Datos del evento en JSONB
            $table->timestamp('occurred_at')->useCurrent(); // Timestamp del evento
            $table->unsignedBigInteger('sequence')->nullable(); // Orden determinista para replay
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('game_id')->references('id')->on('trivia_games')->onDelete('cascade');

            // Índices optimizados para análisis y replay
            $table->index(['game_id', 'occurred_at']); // Queries por tiempo
            $table->index(['game_id', 'type']); // Queries por tipo de evento
            $table->index(['game_id', 'sequence']); // Replay determinista
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_game_events');
    }
};
