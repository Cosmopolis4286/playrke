<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'game_modes'.
     */
    public function up(): void
    {
        Schema::create('game_modes', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del modo de juego');

            // Descripción legible y única
            $table->string('description', 50)
                ->unique()
                ->comment('Descripción del modo de juego');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            // Timestamps automáticos
            $table->timestamps();

            // Índice por estado para consultas rápidas
            $table->index(['status', 'description']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'game_modes'.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_modes');
    }
};
