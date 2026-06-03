<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('currencies_game_rooms')) {
            return;
        }

        Schema::create('currencies_game_rooms', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;

            // Relaciones principales
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->foreignSnowflake('game_mode_id')
                ->nullable()
                ->constrained('game_modes')
                ->nullOnDelete();

            $table->string('room_id');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();

            $table->timestamps();

            // Evitar duplicados: misma combinación no debe repetirse
            $table->unique(['game_id', 'game_mode_id', 'room_id', 'currency_id'], 'uniq_game_mode_room_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies_game_rooms');
    }
};
