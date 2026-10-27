<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_game_questions');
    }

    public function up() : void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_game_questions')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_game_questions', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('game_id', 20);
            $table->string('question_id', 20);
            $table->unsignedInteger('order_index'); // Orden en este juego específico (sin negativos)
            $table->unsignedInteger('time_limit_seconds'); // Snapshot desde questions
            $table->unsignedInteger('points')->default(100); // Snapshot desde questions
            $table->boolean('is_closed')->default(false); // Cerrada por tiempo o lógica
            $table->timestamp('sent_at')->nullable(); // Cuándo se envió
            $table->timestamp('answered_at')->nullable(); // Momento de cierre (respuestas o timeout)
            $table->unsignedInteger('question_duration_ms')->nullable(); // Duración real para analytics
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('game_id')->references('id')->on('trivia_games')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('trivia_questions')->onDelete('cascade');

            // Índices críticos para performance
            $table->unique(['game_id', 'order_index']); // Garantiza orden único, sin duplicados; cubre también queries simples por game_id
            $table->index(['game_id', 'question_id']); // Útil para debugging y replay
            $table->index('question_id'); // Para buscar en qué juegos se usó una pregunta
        });
    }
};
