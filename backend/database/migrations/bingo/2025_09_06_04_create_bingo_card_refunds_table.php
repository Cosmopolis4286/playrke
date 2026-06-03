<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bingo_card_refunds')) {
            return;
        }

        Schema::create('bingo_card_refunds', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del reembolso');

            $table->foreignSnowflake('card_id')
                ->constrained('bingo_cards')
                ->cascadeOnDelete()
                ->comment('Cartón reembolsado');

            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada al reembolso');

            $table->foreignSnowflake('room_user_id')
                ->constrained('bingo_room_users')
                ->cascadeOnDelete()
                ->comment('Usuario en la sala que solicitó el reembolso');

            $table->foreignSnowflake('requester_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que solicitó el reembolso');

            $table->foreignSnowflake('processor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que procesó el reembolso (admin/sistema)');

            // Información del reembolso
            $table->decimal('original_amount', 12, 2)
                ->unsigned()
                ->comment('Monto original pagado');

            $table->decimal('refund_amount', 12, 2)
                ->unsigned()
                ->comment('Monto reembolsado');

            $table->decimal('commission_retained', 12, 2)
                ->unsigned()
                ->default(0)
                ->comment('Comisión retenida por la plataforma');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete()
                ->comment('Moneda del reembolso');

            // Motivo y estado
            $table->enum('refund_reason', ['CANCELLED_ROOM', 'DUPLICATE_PURCHASE', 'TECHNICAL_ERROR', 'USER_REQUEST', 'OTHER'])
                ->comment('Motivo del reembolso');

            $table->text('reason_description')
                ->nullable()
                ->comment('Descripción detallada del motivo');

            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'PROCESSED', 'CANCELLED'])
                ->default('PENDING')
                ->comment('Estado del reembolso');

            // Referencias externas
            $table->string('payment_reference', 100)
                ->nullable()
                ->comment('Referencia del pago original');

            $table->string('refund_reference', 100)
                ->nullable()
                ->unique()
                ->comment('Referencia única del reembolso');

            $table->string('transaction_id', 100)
                ->nullable()
                ->comment('ID de transacción del procesador de pagos');

            // Fechas importantes
            $table->timestamp('requested_at')
                ->comment('Fecha y hora de solicitud del reembolso');

            $table->timestamp('processed_at')
                ->nullable()
                ->comment('Fecha y hora de procesamiento del reembolso');

            $table->timestamp('completed_at')
                ->nullable()
                ->comment('Fecha y hora de completion del reembolso');

            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes
            $table->index('card_id', 'idx_bingo_card_refunds_card');
            $table->index('room_id', 'idx_bingo_card_refunds_room');
            $table->index('room_user_id', 'idx_bingo_card_refunds_room_user');
            $table->index('requester_user_id', 'idx_bingo_card_refunds_requester');
            $table->index(['status', 'requested_at'], 'idx_bingo_card_refunds_status_requested');
            $table->index(['refund_reason', 'status'], 'idx_bingo_card_refunds_reason_status');
            $table->index('refund_reference', 'idx_bingo_card_refunds_reference');
            $table->index('processed_at', 'idx_bingo_card_refunds_processed');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Evita múltiples reembolsos para el mismo cartón
            $table->unique(['card_id'], 'unique_refund_per_card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_refunds');
    }
};
