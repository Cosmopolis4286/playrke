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
        Schema::dropIfExists('jackpots');
    }

    /**
     * Run the migrations.
     *
     * Saldo actual acumulado de cada jackpot (un pozo por jackpot_type_id,
     * creado bajo demanda al primer aporte). Análogo a `wallets`, con el
     * historial de movimientos en `jackpot_movements`.
     */
    public function up() : void
    {
        Schema::create('jackpots', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del jackpot');

            $table->foreignSnowflake('jackpot_type_id')
                ->unique()
                ->constrained('jackpot_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Tipo de jackpot al que pertenece este pozo — un pozo único por tipo');

            $table->decimal('amount', 15, 2)->default(0)->comment('Saldo acumulado actual del pozo');

            $table->timestamps();
        });
    }
};
