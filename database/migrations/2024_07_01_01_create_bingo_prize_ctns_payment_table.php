<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_prize_ctns_payment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prize_ctn_id')->constrained('bingo_prize_ctns');
            $table->enum('payment', ['CS', 'CC', 'SP']);

            $table->foreignId('room_id')->constrained('bingo_rooms');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('external_player_id')->constrained('external_players')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID de la moneda asociada');
            $table->decimal('amount', 16, 8);
            $table->string('payment_proof', 25);

            $table->timestamps();

            $table->index(['room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_prize_ctns_payment');
    }
};
