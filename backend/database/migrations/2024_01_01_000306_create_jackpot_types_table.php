<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('jackpot_types');
    }

    /**
     * Run the migrations.
     *
     * Catálogo per-game de tipos de jackpot. Cada tipo pertenece a un único
     * juego y a una única moneda (un jackpot semanal en USD y otro en Bs son
     * pozos distintos, cada uno con su propio saldo en `jackpots`).
     */
    public function up() : void
    {
        Schema::create('jackpot_types', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del tipo de jackpot');

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete()
                ->comment('Juego al que pertenece este tipo de jackpot');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Moneda del pozo acumulado');

            $table->string('code', 30)
                ->comment('Código único del tipo de jackpot');

            $table->string('label', 50)->comment('Etiqueta legible del tipo de jackpot');

            $table->enum('periodicity', ['PER_ROOM', 'WEEKLY', 'BIWEEKLY', 'MONTHLY'])
                ->default('PER_ROOM')
                ->comment('PER_ROOM: se acumula y aplica dentro de una sola sala. Las demás son pozos compartidos entre salas.');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            $table->unique(['game_id', 'code'], 'uniq_jackpot_types_game_code');
            $table->index('status', 'idx_jackpot_types_status');
        });
    }
};
