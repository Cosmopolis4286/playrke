<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('trivia_games')->onDelete('cascade');
            $table->string('player_id'); // Referencia externa, no FK
            $table->integer('final_score')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('wrong_answers')->default(0);
            $table->float('average_response_time_ms')->default(0);
            $table->integer('position')->default(0); // Posición final
            $table->integer('max_streak')->default(0);
            $table->json('metadata')->nullable(); // Historial detallado
            $table->timestamps();

            $table->index(['game_id', 'position']);
            $table->index(['game_id', 'player_id']);
            $table->index('player_id');
            $table->unique(['game_id', 'player_id'], 'unique_score');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_scores');
    }
};
