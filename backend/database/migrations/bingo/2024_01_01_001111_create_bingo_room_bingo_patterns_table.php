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
        Schema::create('bingo_room_bingo_patterns', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la relación sala-patrón');

            $table->foreignSnowflake('bingo_room_config_id')
                ->constrained('bingo_rooms_config')
                ->cascadeOnDelete()
                ->comment('Configuración de sala asociada');

            $table->foreignSnowflake('bingo_pattern_id')
                ->constrained('bingo_patterns')
                ->cascadeOnDelete()
                ->comment('Patrón de bingo permitido');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index('bingo_room_config_id', 'idx_bingo_room_patterns_config');
            $table->index('bingo_pattern_id', 'idx_bingo_room_patterns_pattern');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Evita duplicados de tipo de juego por configuración de sala
            $table->unique(['bingo_room_config_id', 'bingo_pattern_id'], 'bingo_room_config_pattern_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_bingo_patterns');
    }
};
