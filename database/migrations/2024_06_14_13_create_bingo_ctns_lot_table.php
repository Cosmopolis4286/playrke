<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_ctns_lot'.
     */
    public function up(): void
    {
        Schema::create('bingo_ctns_lot', function (Blueprint $table) {
            // ID único tipo Snowflake
            $table->snowflake('id')->primary()->comment('ID único del lote de cartones');

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
                ->comment('Usuario creador del lote');

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnUpdate()
                ->comment('Vendedor asociado si aplica');

            $table->foreignId('room_user_id')
                ->constrained('bingo_room_users')
                ->cascadeOnUpdate()
                ->comment('Jugador propietario del lote');

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda utilizada');

            // Datos del lote
            $table->integer('ctns')->comment('Cantidad de cartones');
            $table->decimal('ctn_price', 10, 2)->comment('Precio unitario del cartón');
            $table->decimal('exchange_rate', 10, 2)->comment('Tipo de cambio aplicado');
            $table->decimal('sale_amount', 10, 2)->comment('Monto total de la venta');
            $table->decimal('seller_commn', 10, 2)->nullable()->comment('Comisión del vendedor');
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['room_id', 'room_user_id'], 'idx_room_user');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_ctns_lot'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns_lot');
    }
};
