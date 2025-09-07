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
        Schema::create('bingo_room_config_allowed_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignSnowflake('bingo_room_config_id', 'fk_brc_allowed_config')
                ->constrained('bingo_rooms_config')
                ->cascadeOnDelete();
            $table->foreignSnowflake('currency_id', 'fk_brc_allowed_currency')
                ->constrained('currencies')
                ->cascadeOnUpdate()->cascadeOnDelete()
                ->comment('ID moneda asociada');
            $table->timestamps();

            $table->unique(['bingo_room_config_id', 'currency_id'], 'unique_config_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_config_allowed_currencies');
    }
};
