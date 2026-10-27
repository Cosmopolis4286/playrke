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
        Schema::dropIfExists('bingo_round_draws');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('bingo_round_draws', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la relación ronda-sorteo');

            $table->foreignSnowflake('round_id')
                ->constrained('bingo_rounds')
                ->cascadeOnDelete()
                ->comment('Ronda de bingo asociada');

            $table->foreignSnowflake('draw_id')
                ->constrained('bingo_draws')
                ->cascadeOnDelete()
                ->comment('Sorteo asociado');

            $table->integer('draw_order')->unsigned()->comment('Orden del sorteo dentro de la ronda');

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index('round_id', 'idx_bingo_round_draws_round');
            $table->index('draw_id', 'idx_bingo_round_draws_draw');
            $table->index(['round_id', 'draw_order'], 'idx_bingo_round_draws_round_order');
            // NOTA: índices en created_at/updated_at eliminados - gestionados por Laravel

            // Evita duplicados: mismo sorteo no puede estar en múltiples rondas
            $table->unique(['round_id', 'draw_id'], 'unique_round_draw');
        });
    }
};
