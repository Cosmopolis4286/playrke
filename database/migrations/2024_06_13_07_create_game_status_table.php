<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'game_status'.
     */
    public function up(): void
    {
        Schema::create('game_status', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del estado del juego');

            // Código único del estado
            $table->string('value', 15)
                ->unique()
                ->comment('Código único del estado (ej: ACTIVE, FINISHED)');

            // Descripción legible
            $table->string('description', 100)
                ->comment('Descripción legible del estado');

            // Timestamps automáticos
            $table->timestamps();

            // Índice por código y descripción para consultas rápidas
            $table->index(['value', 'description']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'game_status'.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_status');
    }
};
