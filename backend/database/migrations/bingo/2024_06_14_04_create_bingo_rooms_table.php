<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_rooms'.
     */
    public function up(): void
    {
        Schema::create('bingo_rooms', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la sala');

            // Código único adicional
            $table->string('uuid_code', 36)->unique()->comment('UUID único de la sala');

            // Estado de la sala (FK)
            $table->foreignSnowflake('status_id')
                ->constrained('game_status')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Estado de la sala (activo, finalizado, etc.)');

            // Usuario creador
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que creó la sala');

            // Premios
            $table->enum('paid_prizes', ['Y', 'N'])
                ->default('N')
                ->comment('Indica si los premios fueron pagados');

            // Campos financieros
            $table->decimal('prize_pool', 15, 2)->default(0)->comment('Pool de premios de la sala');
            $table->decimal('full_house_prize', 15, 2)->default(0)->comment('Premio por línea completa');
            $table->decimal('serial_prize', 15, 2)->default(0)->comment('Premio por serial');

            // Comisiones
            $table->decimal('rke_commission', 8, 2)->default(0)->comment('Comisión del RKE');
            $table->decimal('room_commission', 8, 2)->default(0)->comment('Comisión de la sala');
            $table->decimal('seller_commission', 8, 2)->default(0)->comment('Comisión del vendedor');

            // Contadores
            $table->integer('total_players')->default(0)->comment('Número de jugadores en la sala');
            $table->integer('total_cards')->default(0)->comment('Número total de cartones vendidos');

            // Campo financiero principal (los demás están en bingo_rooms_config)
            $table->decimal('total_sales', 15, 2)->default(0)->comment('Ventas totales de la sala (resumen)');

            // Fecha de publicación
            $table->timestamp('published_at')->nullable()->comment('Fecha de publicación de la sala');
            // Fecha de finalización
            $table->timestamp('finished_at')->nullable()->comment('Fecha de finalización de la sala');

            // Timestamps automáticos
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índices compuestos útiles
            $table->index(['creator_user_id', 'created_at'], 'idx_creator_createdat');
            $table->index(['creator_user_id', 'status_id'], 'idx_creator_status');
            $table->index(['finished_at'], 'idx_finished_at');
            $table->index('uuid_code', 'idx_uuid_code');
            $table->index(['paid_prizes', 'finished_at'], 'idx_paid_prizes_finished');
            // NOTA: índice en updated_at eliminado - Laravel ya gestiona timestamps eficientemente
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_rooms'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_rooms');
    }
};
