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
            $table->id()->comment('ID del modo de juego');
            $table->string('description', 50)->comment('Descripción del modo de juego');
            $table->boolean('status')->default(1)->comment('Estado activo/inactivo');
            $table->timestamps();
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
