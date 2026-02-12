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

            // Identificador encriptado
            $table->string('encrypted_code', 18)->unique()
                ->comment('Código encriptado único de la sala');

            // Estado de la sala (FK)
            $table->foreignSnowflake('status_id')
                ->constrained('game_status')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->default(1)
                ->comment('Estado de la sala (activo, finalizado, etc.)');

            // Tipo de sala
            $table->enum('room_type', ['PV', 'PB'])
                ->default('PV')
                ->comment('Tipo de sala: PV = público, PB = privado');

            // Usuario creador
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que creó la sala');

            // Premios
            $table->enum('prizes_paid', ['Y', 'N'])
                ->default('N')
                ->comment('Indica si los premios fueron pagados');

            // Fechas y contadores
            $table->timestamp('publication_date')->nullable()->comment('Fecha de publicación de la sala');
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

            // Cierre de sala
            $table->timestamp('closed_at')->nullable()->comment('Fecha y hora de cierre de la sala');

            // Código único adicional
            $table->string('uuid_code', 36)->nullable()->unique()->comment('UUID adicional de la sala');

            // Timestamps automáticos
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Índices compuestos útiles
            $table->index(['status_id', 'room_type'], 'idx_status_roomtype');
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
