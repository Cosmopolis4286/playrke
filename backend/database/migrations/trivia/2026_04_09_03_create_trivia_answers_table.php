<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('game_id');
            $table->foreign('game_id')->references('id')->on('trivia_games')->onDelete('cascade');
            $table->string('player_id'); // Referencia externa, no FK
            $table->ulid('question_id');
            $table->foreign('question_id')->references('id')->on('trivia_questions');
            $table->string('submitted_answer', 255);
            $table->boolean('is_correct');
            $table->integer('points_earned')->default(0);
            $table->float('response_time_ms');
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->index(['game_id', 'player_id']);
            $table->index(['game_id', 'question_id']);
            $table->index('player_id');
            $table->unique(['game_id', 'player_id', 'question_id'], 'unique_answer');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_answers');
    }
};
