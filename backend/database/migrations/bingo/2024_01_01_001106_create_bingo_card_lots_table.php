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

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador del lote');

            $table->foreignSnowflake('seller_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario vendedor asociado si aplica');

            // Tipo de vendedor para auditoría
            $table->enum('seller_type', ['USER', 'SELLER', 'COMMERCE', 'CUSTOMER'])
                ->nullable()
                ->comment('Tipo de vendedor: USER = usuario normal, SELLER = vendedor oficial, COMMERCE = comercio, CUSTOMER = cliente API');

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

            // Cuánto de sale_amount se pagó con bonus_balance (no retirable) en
            // vez de balance real. Permite reembolsar proporcionalmente a cada
            // columna si la sala se cancela (ver WalletService::recordRefund()
            // y BingoCardLotRefundJob).
            $table->decimal('bonus_amount', 15, 2)->default(0.00)->after('sale_amount');

            $table->decimal('commission_amount', 10, 2)
                ->nullable()
                ->unsigned()
                ->comment('Comisión del vendedor');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'REFUNDED'])->default('ACTIVE');

            // True cuando al menos uno de los cartones del lote fue personalizado por el jugador.
            // Permite filtrar/reportar lotes con selección manual sin hacer JOIN a bingo_cards.
            $table->boolean('has_custom_cards')->default(false)->after('status');

            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes y rendimiento
            $table->index(['room_id', 'room_user_id'], 'idx_bingo_card_lots_room_user');
            $table->index(['room_id', 'status'], 'idx_bingo_card_lots_room_active');
            $table->index(['seller_user_id', 'status'], 'idx_bingo_card_lots_seller_active');
            // room_user_id se filtra solo (sin room_id) en BingoCardLotRepository
            $table->index('room_user_id', 'idx_bingo_card_lots_room_user_id');
            // whereBetween('created_at', ...) en reportes por rango de fecha
            $table->index('created_at', 'idx_bingo_card_lots_created_at');
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
