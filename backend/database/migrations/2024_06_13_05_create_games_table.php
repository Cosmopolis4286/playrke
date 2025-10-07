<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'games'.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del juego');

            // Nombre legible y descriptivo
            $table->string('name', 50)
                ->unique()
                ->comment('Nombre del juego');

            // Estado del juego
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'MAINTENANCE'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo');

            // Timestamps automáticos
            $table->timestamps();

            // Índice por estado para consultas rápidas
            $table->index(['status', 'name']);
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'games'.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
