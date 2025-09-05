<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'play_history'.
     */
    public function up(): void
    {
        Schema::create('play_history', function (Blueprint $table) {
            $table->id()->comment('ID del historial de juego');
            $table->date('date')->comment('Fecha del juego');
            $table->time('time')->comment('Hora del juego');
            $table->foreignId('game_id')->constrained('games')->onUpdate('cascade')->onDelete('cascade')->comment('Juego asociado');
            $table->unsignedBigInteger('room_id')->comment('Sala o room asociada');
            $table->foreignId('coin_id')->constrained('coins')->cascadeOnUpdate()->comment('Moneda utilizada');
            $table->decimal('gain', 12, 2)->comment('Ganancia obtenida');
            $table->string('action', 50)->comment('Acción realizada');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade')->comment('Usuario que jugó');
            $table->timestamps();
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'play_history'.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_history');
    }
};
