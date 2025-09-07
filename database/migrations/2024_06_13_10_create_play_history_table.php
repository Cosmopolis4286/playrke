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
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del historial de juego');

            // Fecha y hora del juego
            $table->date('date')->comment('Fecha del juego');
            $table->time('time')->comment('Hora del juego');

            // Relaciones Snowflake
            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Juego asociado');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda utilizada');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que jugó');

            // Sala o room asociada (si quieres relacionarla con otra tabla, se puede adaptar)
            $table->snowflake('room_id')->comment('Sala o room asociada');

            // Detalles del juego
            $table->decimal('gain', 12, 2)->default(0.00)->comment('Ganancia obtenida');
            $table->string('action', 50)->comment('Acción realizada');

            // Timestamps automáticos
            $table->timestamps();

            // Índices compuestos para consultas frecuentes
            $table->index(['user_id', 'game_id', 'date']);
            $table->index(['room_id', 'game_id']);
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
