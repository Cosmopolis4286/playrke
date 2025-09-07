<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bingo_room_play_types', function (Blueprint $table) {
            $table->id();

            $table->foreignSnowflake('bingo_room_config_id')
                ->constrained('bingo_rooms_config')
                ->cascadeOnDelete()
                ->comment('Configuración de sala asociada');

            $table->foreignSnowflake('play_type_id')
                ->constrained('bingo_patterns')
                ->cascadeOnDelete()
                ->comment('Tipo de jugada permitida');

            $table->timestamps();

            // Evita duplicados de tipo de juego por configuración de sala
            $table->unique(['bingo_room_config_id', 'play_type_id'], 'bingo_room_config_play_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_play_types'); 
    }
};
