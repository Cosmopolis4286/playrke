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
            $table->id()->comment('ID del premio del cartón');

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
            $table->string('game_round', 10)
                ->comment('Ronda del juego');
            
            $table->boolean('is_active')
                ->default(false)
                ->comment('Estado de activación del premio: true = activo, false = inactivo');
            
            $table->enum('prize_type', ['full_house', 'single_line', 'serial'])
                ->comment('Tipo de premio: full_house = Casa Llena, single_line = Línea Simple, serial = Serial');
            
            $table->string('winning_pattern', 100)
                ->comment('Patrón del cartón ganador');
            
            $table->decimal('usd_amount', 12, 2)
                ->unsigned()
                ->comment('Monto en USD');
            
            $table->decimal('local_amount', 12, 8)
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
            $table->index(['game_round', 'room_id'], 'idx_bingo_card_prizes_round_room');
            $table->index('claimed_at', 'idx_bingo_card_prizes_claimed');
            $table->index('verification_code', 'idx_bingo_card_prizes_verification');
            $table->index('created_at', 'idx_bingo_card_prizes_created');

            // Nota: Validaciones de integridad se manejan a nivel de aplicación
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
