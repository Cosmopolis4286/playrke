<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'game_mode_relationship'.
     */
    public function up(): void
    {
        Schema::create('game_mode_relationship', function (Blueprint $table) {
            $table->id()->comment('ID de la relación entre juego y modo');
            $table->unsignedBigInteger('game_id')->comment('ID del juego');
            $table->unsignedBigInteger('game_mode_id')->comment('ID del modo de juego');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')
                ->comment('Estado activo/inactivo de la relación');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('game_id')->references('id')->on('games')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('game_mode_id')->references('id')->on('game_modes')->onUpdate('cascade')->onDelete('cascade');

            // Índices para consultas más rápidas
            $table->unique(['game_id', 'game_mode_id'], 'game_mode_unique');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'game_mode_relationship'.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_mode_relationship');
    }
};
