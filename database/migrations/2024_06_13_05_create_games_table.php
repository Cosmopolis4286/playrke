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
            $table->id()->comment('ID del juego');
            $table->string('name', 50)->comment('Nombre del juego');
            $table->enum('status', ['active', 'inactive'])->default('active')
                ->comment('Estado activo/inactivo');
            $table->timestamps();
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
