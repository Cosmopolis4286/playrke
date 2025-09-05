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
        Schema::create('bingo_room_config_allowed_coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bingo_room_config_id')
                ->constrained('bingo_rooms_config')
                ->cascadeOnDelete();
            $table->foreignId('coin_id')->constrained('coins')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID moneda asociada');
            $table->timestamps();

            $table->unique(['bingo_room_config_id', 'coin'], 'unique_config_coin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_config_allowed_coins');
    }
};
