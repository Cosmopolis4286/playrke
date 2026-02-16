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
            $table->string('id', 50)
                ->primary()
                ->comment('Código único del estado del juego (ej. UNAVAILABLE, AVAILABLE, IN_PROGRESS, etc.)');

            // Campo legible para mostrar en interfaces, con un límite de 100 caracteres
            $table->string('label', 100)
                ->comment('Descripción legible del estado');

            // Timestamps automáticos
            $table->timestamps();

            $table->softDeletes();

            // Índice por código y descripción para consultas rápidas
            $table->index(['id', 'label']);
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
