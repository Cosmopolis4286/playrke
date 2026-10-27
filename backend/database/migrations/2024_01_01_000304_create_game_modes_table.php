<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'game_modes'.
     */
    public function down() : void
    {
        Schema::dropIfExists('game_modes');
    }

    /**
     * Ejecutar la migración: crear tabla 'game_modes'.
     *
     * Catálogo per-game de modalidades de participación reales (Solitario,
     * Múltiple, Ranking, etc.). Cada modo pertenece a un único juego
     * (game_id NOT NULL) — no se comparten modos entre juegos.
     */
    public function up() : void
    {
        Schema::create('game_modes', function(Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del modo de juego');

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete()
                ->comment('Juego al que pertenece este modo');

            $table->string('code', 30)
                ->comment('Código único del modo de juego.');

            // Descripción legible
            $table->string('label', 50)
                ->comment('Descripción del modo de juego');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            // Timestamps automáticos
            $table->timestamps();

            $table->unique(['game_id', 'code'], 'uniq_game_modes_game_code');
            $table->unique(['game_id', 'label'], 'uniq_game_modes_game_label');

            // Índice por estado para consultas rápidas
            $table->index(['status', 'label']);
        });
    }
};
