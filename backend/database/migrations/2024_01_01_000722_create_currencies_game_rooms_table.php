<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies_game_rooms', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();

            // Relaciones principales
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->foreignSnowflake('connection_type_id')
                ->nullable()
                ->constrained('connection_types')
                ->nullOnDelete();

            $table->string('room_id');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();

            $table->timestamps();

            // Evitar duplicados: misma combinación no debe repetirse
            $table->unique(['game_id', 'connection_type_id', 'room_id', 'currency_id'], 'uniq_connection_type_room_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies_game_rooms');
    }
};
