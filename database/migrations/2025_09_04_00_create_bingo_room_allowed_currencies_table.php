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
        Schema::create('bingo_room_allowed_currencies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Definir claves foráneas 
            $table->foreignSnowflake('bingo_room_config_id')
                  ->references('id')->on('bingo_rooms_config')
                  ->onDelete('cascade');
            
            $table->foreignSnowflake('currency_id')
                  ->references('id')->on('currencies')
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['bingo_room_config_id', 'currency_id'], 'unique_config_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bingo_room_allowed_currencies');
    }
};
