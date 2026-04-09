<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->create('trivia_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('trivia_rooms')->onDelete('cascade');
            $table->string('game_code', 10)->unique();
            $table->string('status', 20)->default('active'); // active, paused, finished, cancelled
            $table->integer('current_question_index')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('player_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->timestamps();

            $table->index('status');
            $table->index('game_code');
            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_games');
    }
};
