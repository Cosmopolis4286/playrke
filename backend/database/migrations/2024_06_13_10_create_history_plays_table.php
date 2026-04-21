<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'history_plays'.
     */
    public function up(): void
    {
        Schema::create('history_plays', function (Blueprint $table) {
            // ID Snowflake único (PRIMARY KEY)
            $table->snowflake('id')->primary()->comment('ID único del historial de juego');

            // Fecha y hora combinadas
            $table->timestamp('played_at')->useCurrent()->comment('Fecha y hora del juego');

            // Claves foráneas Snowflake
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Juego asociado');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Moneda utilizada');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que jugó');

            $table->string('room_id')
                ->comment('ID de sala asociada');

            // Detalles del juego
            $table->decimal('gain', 12, 2)->default(0.00)->comment('Ganancia obtenida');
            $table->string('action', 100)->comment('Acción realizada');

            // Timestamps automáticos
            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['user_id', 'game_id', 'played_at'], 'idx_user_game_date');
            $table->index(['room_id', 'game_id'], 'idx_room_game');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'history_plays'.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_plays');
    }
};
