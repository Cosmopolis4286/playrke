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

            $table->foreignSnowflake('prize_ctn_id')->constrained('bingo_prize_ctns');
            $table->enum('payment', ['CS', 'CC', 'SP']);

            $table->foreignSnowflake('room_id')->constrained('bingo_rooms');
            $table->foreignSnowflake('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignSnowflake('seller_id')->constrained('sellers')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignSnowflake('external_player_id')->constrained('external_players')->onDelete('cascade');
            $table->foreignSnowflake('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignSnowflake('currency_id')->constrained('currencies')
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
