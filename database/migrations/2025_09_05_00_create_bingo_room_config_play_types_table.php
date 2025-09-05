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
        Schema::create('bingo_room_config_play_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bingo_room_config_id')
                ->constrained('bingo_rooms_config')
                ->cascadeOnDelete()
                ->comment('Configuración de sala asociada');

            $table->foreignId('play_type_id')
                ->constrained('bingo_patterns')
                ->restrictOnDelete()
                ->comment('Tipo de juego permitido');

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
        Schema::dropIfExists('bingo_room_config_play_types'); // corregido
    }
};
