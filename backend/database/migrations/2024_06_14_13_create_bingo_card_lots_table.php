<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_card_lots'.
     */
    public function up(): void
    {
        Schema::create('bingo_card_lots', function (Blueprint $table) {
            // ID único tipo Snowflake
            $table->snowflake('id')->primary()->comment('ID único del lote de cartones');

            // Relaciones
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador del lote');

            $table->foreignSnowflake('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Vendedor asociado si aplica');

            $table->foreignSnowflake('room_user_id')
                ->constrained('bingo_room_users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Jugador propietario del lote');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda utilizada');

            // Datos del lote
            $table->integer('card_count')
                ->unsigned()
                ->comment('Cantidad de cartones');
            
            $table->decimal('card_price', 10, 2)
                ->unsigned()
                ->comment('Precio unitario del cartón');
            
            $table->decimal('exchange_rate', 10, 4)
                ->unsigned()
                ->comment('Tipo de cambio aplicado');
            
            $table->decimal('sale_amount', 12, 2)
                ->unsigned()
                ->comment('Monto total de la venta');
            
            $table->decimal('commission_amount', 10, 2)
                ->nullable()
                ->unsigned()
                ->comment('Comisión del vendedor');
            
            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado del lote: true = activo, false = inactivo');

            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes y rendimiento
            $table->index(['room_id', 'room_user_id'], 'idx_bingo_card_lots_room_user');
            $table->index(['room_id', 'is_active'], 'idx_bingo_card_lots_room_active');
            $table->index(['seller_id', 'is_active'], 'idx_bingo_card_lots_seller_active');
            $table->index('created_at', 'idx_bingo_card_lots_created');

            // Índice único para evitar duplicados
            $table->unique(['room_id', 'room_user_id', 'created_at'], 'unq_bingo_card_lots_room_user_time');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_card_lots'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_lots');
    }
};
