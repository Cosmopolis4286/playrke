<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_card_prize_payments'.
     */
    public function up(): void
    {
        Schema::create('bingo_card_prize_payments', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del pago de premio');

            // Relaciones
            $table->foreignSnowflake('card_prize_id')
                ->constrained('bingo_card_prizes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Premio del cartón asociado');

            $table->enum('payment_method', ['seller_cash', 'credit_card', 'system_payment'])
                ->comment('Método de pago: seller_cash = Efectivo del Vendedor, credit_card = Tarjeta de Crédito, system_payment = Pago del Sistema');

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

            $table->foreignSnowflake('external_player_id')
                ->nullable()
                ->constrained('external_players')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Jugador externo asociado');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que recibe el pago');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda asociada');

            // Datos del pago
            $table->decimal('payment_amount', 16, 8)
                ->unsigned()
                ->comment('Monto del pago del premio');

            $table->string('payment_proof', 500)
                ->comment('Prueba o comprobante del pago del premio');

            // Estado del pago
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'cancelled'])
                ->default('pending')
                ->comment('Estado del pago: pending = pendiente, completed = completado, failed = fallido, cancelled = cancelado');

            // Campos de auditoría y control
            $table->timestamp('processed_at')
                ->nullable()
                ->comment('Fecha y hora de procesamiento del pago');

            $table->string('transaction_reference', 100)
                ->nullable()
                ->unique()
                ->comment('Referencia única de la transacción');

            $table->text('payment_notes')
                ->nullable()
                ->comment('Notas adicionales sobre el pago');

            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas frecuentes y rendimiento
            $table->index(['room_id', 'user_id'], 'idx_bingo_card_prize_payments_room_user');
            $table->index(['card_prize_id', 'payment_status'], 'idx_bingo_card_prize_payments_prize_status');
            $table->index(['payment_method', 'payment_status'], 'idx_bingo_card_prize_payments_method_status');
            $table->index(['seller_id', 'payment_status'], 'idx_bingo_card_prize_payments_seller_status');
            $table->index(['external_player_id', 'payment_status'], 'idx_bingo_card_prize_payments_player_status');
            $table->index('processed_at', 'idx_bingo_card_prize_payments_processed');
            $table->index('transaction_reference', 'idx_bingo_card_prize_payments_reference');
            $table->index('created_at', 'idx_bingo_card_prize_payments_created');
            $table->index('updated_at', 'idx_bingo_card_prize_payments_updated');

            // Nota: Validaciones de integridad se manejan a nivel de aplicación
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_card_prize_payments'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_prize_payments');
    }
};
