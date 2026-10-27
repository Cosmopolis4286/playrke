<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración: eliminar tabla 'game_status'.
     */
    public function down() : void
    {
        Schema::dropIfExists('game_status');
    }

    /**
     * Ejecutar la migración: crear tabla 'game_status'.
     */
    public function up() : void
    {
        Schema::create('game_status', function(Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del modo de juego');

            $table->string('code', 30)
                ->unique()
                ->comment('Código único del estado del juego.');

            // Campo legible para mostrar en interfaces, con un límite de 100 caracteres
            $table->string('label', 50)
                ->comment('Descripción legible del estado');

            // Timestamps automáticos
            $table->timestamps();

            $table->softDeletes();

            // Índice por label para consultas rápidas
            $table->index('label');
        });
    }
};
