<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_card_prices'.
     */
    public function up(): void
    {
        Schema::create('bingo_card_prices', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID del precio del cartón');

            // Relaciones
            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala de bingo a la que aplica el precio');

            $table->foreignSnowflake('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador que estableció el precio');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Moneda asociada al precio');

            // Precio del cartón
            $table->decimal('card_price', 12, 2)
                ->comment('Precio del cartón de bingo en la moneda asociada');

            $table->timestamps();

            $table->softDeletes();

            // Evita duplicados: mismo room y misma moneda no deben repetirse
            $table->unique(['room_id', 'currency_id'], 'uniq_room_currency_price');

            // Índices para consultas por fecha
            $table->index('created_at', 'idx_bingo_card_prices_created');
            $table->index('updated_at', 'idx_bingo_card_prices_updated');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_card_prices'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_card_prices');
    }
};
