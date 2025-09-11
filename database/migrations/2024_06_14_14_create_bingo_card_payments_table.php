<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_card_payments'.
     */
    public function up(): void
    {
        Schema::create('bingo_card_payments', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del pago');

            // Método de pago
            $table->enum('payment_method', ['payment_system', 'seller_cash'])
                ->comment('Método de pago: payment_system = Sistema de Pago, seller_cash = Efectivo del Vendedor');

            $table->string('description', 255)
                ->nullable()
                ->comment('Descripción opcional del pago');

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
                ->comment('Usuario creador del pago');

            $table->foreignSnowflake('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Vendedor asociado si aplica');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que realizó el pago');

            $table->foreignSnowflake('external_player_id')
                ->nullable()
                ->constrained('external_players')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Jugador externo asociado');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda utilizada');

            $table->foreignSnowflake('card_lot_id')
                ->constrained('bingo_card_lots')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Lote de cartones asociado');

            // Datos del pago
            $table->decimal('payment_amount', 12, 2)
                ->unsigned()
                ->comment('Monto del pago');
            
            $table->string('payment_proof', 500)
                ->comment('Prueba o comprobante del pago');

            // Estado del pago
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'cancelled'])
                ->default('pending')
                ->comment('Estado del pago: pending = pendiente, completed = completado, failed = fallido, cancelled = cancelado');

            // Campos de auditoría
            $table->timestamp('processed_at')
                ->nullable()
                ->comment('Fecha y hora de procesamiento del pago');
            
            $table->string('transaction_reference', 100)
                ->nullable()
                ->comment('Referencia de la transacción externa');

            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas frecuentes y rendimiento
            $table->index(['room_id', 'seller_id'], 'idx_bingo_card_payments_room_seller');
            $table->index(['user_id', 'external_player_id'], 'idx_bingo_card_payments_user_external');
            $table->index(['payment_method', 'payment_status'], 'idx_bingo_card_payments_method_status');
            $table->index(['card_lot_id', 'payment_status'], 'idx_bingo_card_payments_lot_status');
            $table->index('processed_at', 'idx_bingo_card_payments_processed');
            $table->index('created_at', 'idx_bingo_card_payments_created');

            // Índice único para evitar pagos duplicados
            $table->unique(['card_lot_id', 'user_id', 'external_player_id', 'created_at'], 'unq_bingo_card_payments_lot_user_time');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_card_payments'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_payments');
    }
};
