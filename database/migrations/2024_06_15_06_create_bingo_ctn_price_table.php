<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_ctn_prices'.
     */
    public function up(): void
    {
        Schema::create('bingo_ctn_prices', function (Blueprint $table) {
            $table->id()->comment('ID del precio del cartón');

            // Relaciones
            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala de bingo a la que aplica el precio');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que estableció el precio');

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Moneda asociada al precio');

            // Precio del cartón
            $table->decimal('ctn_price', 12, 2)
                ->comment('Precio del cartón de bingo en la moneda asociada');

            $table->timestamps();

            // Evita duplicados: mismo room y misma moneda no deben repetirse
            $table->unique(['room_id', 'currency_id'], 'uniq_room_currency_price');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_ctn_prices'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctn_prices');
    }
};
