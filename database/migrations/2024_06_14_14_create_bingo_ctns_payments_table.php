<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_ctns_payments', function (Blueprint $table) {
            $table->id();

            $table->enum('payment', ['PS', 'SC'])
                ->comment('Método de pago: PS = Payment System, SC = Seller Cash');

            $table->string('description', 50)->nullable();

            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete();

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('external_player_id')
                ->constrained('external_players')
                ->cascadeOnDelete();

            $table->foreignId('coin_id')
                ->constrained('coins');

            $table->foreignId('lot_id')
                ->constrained('bingo_ctns_lots');

            $table->decimal('amount', 12, 2);
            $table->string('payment_proof', 100);

            $table->timestamps();

            // Índice compuesto para optimizar búsquedas
            $table->index(['room_id', 'seller_id'], 'idx_room_seller');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns_payments');
    }
};
