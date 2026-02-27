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

            $table->foreignSnowflake('prize_id')
                ->nullable()
                ->constrained('prizes')
                ->nullOnDelete()
                ->comment('ID del premio si existe uno asociado');

            $table->decimal('prize_value', 15, 6)->nullable()->comment('Valor del premio');

            $table->string('prize_description', 60);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_round_prizes');
    }
};
