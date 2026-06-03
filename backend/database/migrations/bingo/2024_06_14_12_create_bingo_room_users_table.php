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
        if (Schema::hasTable('bingo_room_users')) {
            return;
        }

        Schema::create('bingo_room_users', function (Blueprint $table) {
            // ID único tipo Snowflake
            $table->snowflake('id')->primary()->comment('ID único del jugador en la sala');

            // Relaciones
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que participa en la sala');

            $table->enum('role', ['PLAYER', 'SELLER', 'ADMIN', 'PLAYER_SELLER'])
                ->default('PLAYER')
                ->comment('Rol del usuario en la sala: PLAYER = jugador, SELLER = vendedor, ADMIN = administrador, PLAYER_SELLER = ambos roles');

            // Datos del jugador
            $table->unsignedSmallInteger('cards_purchased')->comment('Cantidad de cartones adquiridos');
            $table->decimal('automarker', 8, 2)->nullable()->comment('Valor del automarcador si aplica');
            $table->enum('winner', ['Y', 'N'])->default('N')->comment('Indicador de ganador: Y = ganador, N = no ganador');

            $table->timestamps();

            // Índices para optimizar consultas frecuentes
            $table->index(['room_id', 'user_id'], 'idx_room_user');
            $table->index(['room_id', 'role'], 'idx_room_role');
            $table->index(['user_id', 'role'], 'idx_user_role');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
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
