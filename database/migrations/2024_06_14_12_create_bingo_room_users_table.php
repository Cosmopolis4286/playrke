<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_room_users'.
     */
    public function up(): void
    {
        Schema::create('bingo_room_users', function (Blueprint $table) {
            // ID único tipo Snowflake
            $table->snowflake('id')->primary()->comment('ID único del jugador en la sala');

            // Relaciones
            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que participa en la sala');

            // Datos del jugador
            $table->tinyInteger('ctns')->comment('Cantidad de cartones adquiridos');
            $table->decimal('automarker', 8, 2)->nullable()->comment('Valor del automarcador si aplica');
            $table->enum('winner', ['Y', 'N'])->default('N')->comment('Indicador de ganador: Y = ganador, N = no ganador');

            $table->timestamps();

            // Índices para optimizar consultas frecuentes
            $table->index(['room_id', 'creator_user_id'], 'idx_room_user');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_room_users'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_users');
    }
};
