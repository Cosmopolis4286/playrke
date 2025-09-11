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
        Schema::create('game_room_jackpots', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('currency_id')->constrained('currencies')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID moneda asociada');
            $table->decimal('amount', 12, 4);
            $table->enum('status', ['AVAILABLE', 'USED'])->default('AVAILABLE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_room_jackpots');
    }
};
