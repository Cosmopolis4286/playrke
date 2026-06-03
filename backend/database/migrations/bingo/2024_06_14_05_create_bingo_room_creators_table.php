<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla PIVOT: creadores que participan en salas (propias o ajenas)
     * con comisiones específicas por sala.
     * 
     * NOTA: Un creador puede participar en salas de OTROS creadores
     * con comisiones distintas. Por eso es necesaria esta tabla.
     */
    public function up(): void
    {
        if (Schema::hasTable('bingo_room_creators')) {
            return;
        }

        Schema::create('bingo_room_creators', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del registro');

            // Relaciones
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala donde el creador participa');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID del usuario creador de la sala');

            // Datos financieros
            $table->decimal('creator_commission', 10, 2)
                ->default(0)
                ->comment('Comisión del creador en la sala');

            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE')
                ->comment('Estado del registro del creador');

            // Timestamps automáticos
            $table->timestamps();

            // Evitar duplicados: un creador por sala
            $table->unique(['room_id', 'user_id'], 'unique_creator_per_room');

            // Índices compuestos opcionales para consultas rápidas
            $table->index(['room_id', 'status'], 'idx_room_status');
            $table->index('created_at', 'idx_bingo_creators_created');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_room_creators'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_creators');
    }
};
