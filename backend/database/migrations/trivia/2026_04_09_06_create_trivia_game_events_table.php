<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_game_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('game_id');
            $table->string('type', 50); // question_sent, answer_received, game_started, etc.
            $table->string('player_id')->nullable(); // Quién disparó el evento (opcional)
            $table->jsonb('payload'); // Datos del evento en JSONB
            $table->timestamp('occurred_at'); // Timestamp del evento
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('trivia_games')->onDelete('cascade');
            
            // Índices para análisis y replay
            $table->index(['game_id', 'type']);
            $table->index(['game_id', 'occurred_at']);
            $table->index('type');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_game_events');
    }
};
