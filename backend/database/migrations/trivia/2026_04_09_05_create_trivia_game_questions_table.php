<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_game_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('game_id');
            $table->ulid('question_id');
            $table->integer('order_index'); // Orden en este juego específico
            $table->timestamp('sent_at')->nullable(); // Cuándo se envió
            $table->timestamp('answered_at')->nullable(); // Cuándo respondieron todos
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('trivia_games')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('trivia_questions')->onDelete('cascade');
            
            // Índices críticos para performance
            $table->index(['game_id', 'order_index']);
            $table->index(['game_id', 'question_id']);
            $table->unique(['game_id', 'question_id'], 'unique_game_question');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_game_questions');
    }
};
