<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla bingo_cards
     */
    public function up(): void
    {
        Schema::create('bingo_cards', function (Blueprint $table) {

            /**
             * =========================================================
             * PRIMARY KEY
             * =========================================================
             */
            $table->snowflake('id')
                ->primary()
                ->comment('ID único del cartón de bingo');

            /**
             * =========================================================
             * STATUS
             * =========================================================
             *
             * 1 = ACTIVE
             * 2 = INACTIVE
             * 3 = WINNER
             * 4 = CANCELLED
             */
            $table->unsignedTinyInteger('status')
                ->default(1)
                ->comment('Estado del cartón');

            /**
             * =========================================================
             * RELACIONES
             * =========================================================
             */

            // Sala
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Sala asociada');

            // Usuario creador
            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Usuario creador del cartón');

            // Usuario vendedor/generador
            $table->foreignSnowflake('seller_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario vendedor o generador');

            // Usuario dentro de la sala
            $table->foreignSnowflake('room_user_id')
                ->constrained('bingo_room_users')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Usuario asociado dentro de la sala');

            // Lote del cartón
            $table->foreignSnowflake('card_lot_id')
                ->constrained('bingo_card_lots')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Lote del cartón');

            // Moneda
            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Moneda utilizada');

            /**
             * =========================================================
             * DATOS DEL CARTÓN
             * =========================================================
             */

            $table->string('serial_number', 30)
                ->unique()
                ->comment('Serial único del cartón');

            /**
             * Estructura:
             *
             * {
             *   "B":[1,2,3,4,5],
             *   "I":[16,17,18,19,20],
             *   "N":[31,32,null,34,35],
             *   "G":[46,47,48,49,50],
             *   "O":[61,62,63,64,65]
             * }
             */
            $table->json('card_numbers')
                ->comment('Estructura JSON del cartón');

            /**
             * Hash SHA256 del cartón
             */
            $table->string('card_hash', 64)
                ->unique()
                ->comment('Hash único de integridad');

            /**
             * =========================================================
             * VALIDACIÓN
             * =========================================================
             */

            $table->boolean('is_validated')
                ->default(false)
                ->comment('Indica si el cartón fue validado');

            $table->timestamp('validated_at')
                ->nullable()
                ->comment('Fecha de validación');

            /**
             * =========================================================
             * AUDITORÍA
             * =========================================================
             */
            $table->timestamps();

            $table->softDeletes();

            /**
             * =========================================================
             * ÍNDICES
             * =========================================================
             */

            $table->index(
                ['room_id', 'status'],
                'idx_bingo_cards_room_status'
            );

            $table->index(
                ['room_user_id', 'status'],
                'idx_bingo_cards_room_user_status'
            );

            $table->index(
                ['seller_user_id', 'status'],
                'idx_bingo_cards_seller_status'
            );

            $table->index(
                ['room_id', 'card_lot_id'],
                'idx_bingo_cards_room_lot'
            );

            /**
             * Evita duplicidad lógica del cartón
             */
            $table->unique(
                ['room_id', 'room_user_id', 'card_hash'],
                'uq_bingo_cards_room_user_hash'
            );
        });
    }

    /**
     * Revertir migración
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_cards');
    }
};