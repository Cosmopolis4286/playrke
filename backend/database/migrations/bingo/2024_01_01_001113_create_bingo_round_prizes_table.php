<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('bingo_round_prizes');
    }

    public function up() : void
    {
        Schema::create('bingo_round_prizes', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del premio de ronda');

            $table->foreignSnowflake('bingo_round_id')
                ->constrained('bingo_rounds')
                ->cascadeOnDelete()
                ->comment('Ronda de bingo asociada');

            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->comment('Sala asociada');

            $table->foreignSnowflake('currency_id')
                ->nullable()
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda asociada a la tasa');

            $table->foreignSnowflake('prize_id')
                ->nullable()
                ->constrained('prizes')
                ->nullOnDelete()
                ->comment('ID del premio si existe uno asociado');

            $table->decimal('prize_value', 15, 6)->nullable()->comment('Valor del premio');

            $table->string('prize_description', 60)->comment('Descripción del premio');

            $table->timestamps();
            $table->index(['room_id', 'bingo_round_id'], 'idx_bingo_round_prizes_room_round');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel
        });
    }
};
