<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->integer('room');
            $table->decimal('exchange_rate', 15, 8);
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['game_id', 'room', 'currency_id'], 'unique_exchange_per_game_room_coin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_exchange_rates');
    }
};
