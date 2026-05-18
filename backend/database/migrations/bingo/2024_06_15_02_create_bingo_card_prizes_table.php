<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_card_prizes'.
     */
    public function up(): void
    {
        Schema::create('bingo_card_prizes', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID del premio del cartón');

            // Relaciones
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala del bingo');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador del premio');

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
                ->comment('Usuario en la sala');

            $table->foreignSnowflake('card_lot_id')
                ->constrained('bingo_card_lots')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Lote asociado');

            $table->foreignSnowflake('winning_card_id')
                ->constrained('bingo_cards')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Cartón ganador');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda asociada');

            // Datos del premio
            $table->foreignSnowflake('round_id')
                ->constrained('bingo_rounds')
                ->cascadeOnDelete()
                ->comment('Ronda del premio');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE')
                ->comment('Estado de activación del premio: true = activo, false = inactivo');

            $table->enum('prize_type', ['full_house', 'single_line', 'serial'])
                ->comment('Tipo de premio: full_house = Casa Llena, single_line = Línea Simple, serial = Serial');

            $table->string('winning_pattern', 100)
                ->comment('Patrón del cartón ganador');

            $table->decimal('usd_amount', 12, 2)
                ->unsigned()
                ->comment('Monto en USD');

            $table->decimal('local_amount', 12, 4)
                ->unsigned()
                ->comment('Monto en moneda local');

            $table->enum('prize_status', ['waiting', 'claimed', 'cancelled', 'suspended'])
                ->default('waiting')
                ->comment('Estado del premio: waiting = esperando, claimed = reclamado, cancelled = cancelado, suspended = suspendido');

            // Campos de auditoría
            $table->timestamp('claimed_at')
                ->nullable()
                ->comment('Fecha y hora cuando se reclamó el premio');

            $table->string('verification_code', 20)
                ->nullable()
                ->unique()
                ->comment('Código de verificación del premio');

            $table->text('prize_notes')
                ->nullable()
                ->comment('Notas adicionales sobre el premio');

            $table->timestamps();
            $table->softDeletes();

            // Índices compuestos para búsquedas frecuentes y rendimiento
            $table->index(['room_id', 'winning_card_id'], 'idx_bingo_card_prizes_room_card');
            $table->index(['room_id', 'creator_user_id'], 'idx_bingo_card_prizes_room_creator');
            $table->index(['room_id', 'prize_status'], 'idx_bingo_card_prizes_room_status');
            $table->index(['prize_type', 'prize_status'], 'idx_bingo_card_prizes_type_status');
            $table->index(['card_lot_id', 'prize_status'], 'idx_bingo_card_prizes_lot_status');
            $table->index(['round_id', 'room_id'], 'idx_bingo_card_prizes_round_room');
            $table->index('claimed_at', 'idx_bingo_card_prizes_claimed');
            $table->index('verification_code', 'idx_bingo_card_prizes_verification');
            $table->index(['winning_card_id', 'prize_status'], 'idx_card_prize_status');

            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_card_prizes'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_prizes');
    }
};
