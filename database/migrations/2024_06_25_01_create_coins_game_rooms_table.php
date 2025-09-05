<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coins_game_rooms', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->foreignId('game_mode_id')
                ->nullable()
                ->constrained('game_modes')
                ->nullOnDelete();

            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete();

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnDelete();

            $table->timestamps();

            // Evitar duplicados: misma combinación no debe repetirse
            $table->unique(['game_id', 'game_mode_id', 'room_id', 'coin_id'], 'uniq_game_mode_room_coin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coins_game_rooms');
    }
};
