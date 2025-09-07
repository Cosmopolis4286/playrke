<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla pivot 'game_mode_relationship'.
     */
    public function up(): void
    {
        Schema::create('game_mode_relationship', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la relación juego-modo');

            // Relaciones usando Snowflake
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del juego');

            $table->foreignSnowflake('game_mode_id')
                ->constrained('game_modes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del modo de juego');

            // Estado de la relación
            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo de la relación');

            // Timestamps automáticos
            $table->timestamps();

            // Constraint único para evitar duplicados
            $table->unique(['game_id', 'game_mode_id'], 'game_mode_unique');

            // Índice compuesto opcional para búsquedas frecuentes por estado
            $table->index(['status', 'game_id', 'game_mode_id']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla pivot 'game_mode_relationship'.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_mode_relationship');
    }
};
