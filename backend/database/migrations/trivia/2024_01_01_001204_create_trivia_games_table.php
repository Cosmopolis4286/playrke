<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_games');
    }

    public function up() : void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_games')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_games', function(Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('room_id', 20);
            $table->foreign('room_id')->references('id')->on('trivia_rooms')->onDelete('cascade');
            $table->string('game_code', 10)->unique();
            $table->string('status', 20)->default('playing'); // waiting, playing, paused, finished, cancelled
            $table->unsignedInteger('current_question_index')->default(0);
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('player_count')->default(0);
            $table->unsignedInteger('time_per_question'); // Snapshot desde room
            $table->string('winner_user_id', 20)->nullable(); // Ganador final
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->jsonb('metadata')->nullable(); // Datos adicionales (jsonb para PostgreSQL)
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['room_id', 'status']);
            $table->index(['room_id', 'created_at']); // Historial de partidas por sala
        });
    }
};
