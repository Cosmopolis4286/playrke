<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_round_prizes', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;

            $table->foreignSnowflake('bingo_round_id')
                ->constrained('bingo_rounds')
                ->cascadeOnDelete();

            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete();

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda asociada a la tasa');

            $table->decimal('prize_value', 15, 6)->nullable()->comment('Valor del premio');

            $table->string();

            $table->timestamps();

            // Índice compuesto útil para evitar duplicados de ronda por sala
            $table->unique(['room_id', 'round'], 'unique_round_per_room');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_round_prizes');
    }
};
