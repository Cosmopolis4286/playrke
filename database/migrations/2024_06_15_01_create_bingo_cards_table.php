<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_cards'.
     */
    public function up(): void
    {
        Schema::create('bingo_cards', function (Blueprint $table) {
            $table->id()->comment('ID del cartón de bingo');

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
                ->comment('Creador del cartón');

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
                ->comment('Lote del cartón');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Moneda utilizada');

            $table->string('serial_number', 30)
                ->unique()
                ->comment('Número de serie único del cartón');

            // Columnas del cartón: B, I, N, G, O (5x5 grid)
            foreach (['b', 'i', 'n', 'g', 'o'] as $letter) {
                for ($i = 1; $i <= 5; $i++) {
                    // El centro (N3) es tradicionalmente libre en bingo
                    $nullable = ($letter === 'n' && $i === 3);
                    $table->tinyInteger("{$letter}{$i}")
                        ->nullable($nullable)
                        ->unsigned()
                        ->comment("Número en posición {$letter}{$i} del cartón");
                }
            }

            $table->string('card_hash', 64)
                ->unique()
                ->comment('Hash único del cartón para verificación de integridad');

            // Estado del cartón
            $table->enum('card_status', ['active', 'inactive', 'winner', 'cancelled'])
                ->default('active')
                ->comment('Estado del cartón: active = activo, inactive = inactivo, winner = ganador, cancelled = cancelado');

            // Campos de auditoría
            $table->boolean('is_validated')
                ->default(false)
                ->comment('Indica si el cartón ha sido validado');

            $table->timestamp('validated_at')
                ->nullable()
                ->comment('Fecha y hora de validación del cartón');

            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes y rendimiento
            $table->index(['room_id', 'creator_user_id'], 'idx_bingo_cards_room_creator');
            $table->index(['room_id', 'card_lot_id'], 'idx_bingo_cards_room_lot');
            $table->index(['room_id', 'card_status'], 'idx_bingo_cards_room_status');
            $table->index(['seller_id', 'card_status'], 'idx_bingo_cards_seller_status');
            $table->index(['room_user_id', 'card_status'], 'idx_bingo_cards_user_status');
            $table->index('serial_number', 'idx_bingo_cards_serial');
            $table->index('card_hash', 'idx_bingo_cards_hash');
            $table->index('created_at', 'idx_bingo_cards_created');

            // Nota: Validaciones de integridad se manejan a nivel de aplicación
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_cards'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_cards');
    }
};
