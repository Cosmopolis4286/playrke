<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'bingo_ctns_payments'.
     */
    public function up(): void
    {
        Schema::create('bingo_ctns_payments', function (Blueprint $table) {
            $table->id()->comment('ID único del pago');

            // Tipo de pago
            $table->enum('payment_method', ['PS', 'SC'])
                ->comment('Método de pago: PS = Payment System, SC = Seller Cash');

            $table->string('description', 100)->nullable()->comment('Descripción opcional del pago');

            // Relaciones
            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario creador del pago');

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Vendedor asociado si aplica');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Usuario que realizó el pago');

            $table->foreignId('external_player_id')
                ->constrained('external_players')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Jugador externo asociado');

            $table->foreignId('coin_id')
                ->constrained('coins')
                ->cascadeOnUpdate()
                ->comment('Moneda utilizada');

            $table->foreignId('lot_id')
                ->constrained('bingo_ctns_lot')
                ->cascadeOnUpdate()
                ->comment('Lote de cartones asociado');

            // Datos del pago
            $table->decimal('amount', 12, 2)->comment('Monto del pago');
            $table->string('payment_proof', 255)->comment('Prueba o comprobante del pago');

            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['room_id', 'seller_id'], 'idx_room_seller');
            $table->index(['user_id', 'external_player_id'], 'idx_user_external');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'bingo_ctns_payments'.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns_payments');
    }
};
