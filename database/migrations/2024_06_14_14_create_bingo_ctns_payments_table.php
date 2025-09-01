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
        Schema::create('bingo_ctns_payments', function (Blueprint $table) {
            $table->id();

            $table->enum('payment', ['PS', 'SC'])->comment('Método de pago');
            $table->string('description', 35)->nullable();

            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->onDelete('cascade');

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->onDelete('cascade');

            $table->integer('user')->nullable();

            $table->foreignId('external_player_id')
                ->constrained('external_players')
                ->onDelete('cascade');

            $table->foreignId('coin_id')->constrained('coins');

            $table->foreignId('lot_id')
                ->constrained('bingo_ctns_lot');

            $table->decimal('amount', 12, 2);
            $table->string('payment_proof', 25);

            $table->timestamps();

            // índice compuesto
            $table->index(['room_id', 'seller_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns_payments');
    }
};
