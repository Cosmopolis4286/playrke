<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anti_cheat_flags', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la señal');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que disparó la señal');

            // Sin FK: game_id referencia bingo_rooms (MySQL) o trivia_games (pgsql_trivia)
            // según game_slug — cross-motor, igual que trivia_prize_payments.user_id.
            $table->string('game_slug', 32)->comment('bingo | trivia | futuro slug de juego');
            $table->string('game_id', 64)->nullable()->comment('ID de sala/partida en el motor del juego');

            $table->string('signal_type', 64)->comment('Caso de AntiCheatSignalType');
            $table->unsignedTinyInteger('severity')->default(1)->comment('Peso de la señal para el umbral de suspensión');

            $table->json('metadata')->nullable()->comment('Contexto libre: response_time_ms, expected vs actual, etc.');

            $table->boolean('triggered_suspension')->default(false)
                ->comment('true si esta fila fue la que cruzó el umbral y disparó auto-suspensión');

            $table->timestamps();

            $table->index(['user_id', 'game_slug', 'created_at'], 'idx_flags_user_game_created');
            $table->index(['user_id', 'created_at'], 'idx_flags_user_created');
            $table->index(['game_slug', 'signal_type'], 'idx_flags_game_signal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anti_cheat_flags');
    }
};
