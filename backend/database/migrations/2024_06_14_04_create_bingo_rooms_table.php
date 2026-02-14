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

            // Contadores
            $table->integer('total_players')->default(0)->comment('Número de jugadores en la sala');
            $table->integer('total_cards')->default(0)->comment('Número total de cartones vendidos');

            // Campos financieros
            $table->decimal('total_sales', 15, 2)->default(0)->comment('Ventas totales de la sala');
            $table->decimal('rke_commission', 15, 2)->default(0)->comment('Comisión de la plataforma RKE');
            $table->decimal('room_commission', 15, 2)->default(0)->comment('Comisión de la sala');
            $table->decimal('seller_commission', 15, 2)->default(0)->comment('Comisión del vendedor');
            $table->decimal('prize_pool', 15, 2)->default(0)->comment('Pool de premios acumulado');
            $table->decimal('full_house_prize', 15, 2)->default(0)->comment('Premio por cartón lleno');
            $table->decimal('serial_prize', 15, 2)->default(0)->comment('Premio por serie completa');

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
            $table->index(['finished_at'], 'idx_finished_at');
            $table->index('uuid_code', 'idx_uuid_code');
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
