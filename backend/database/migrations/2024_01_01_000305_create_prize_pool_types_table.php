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
        Schema::dropIfExists('prize_pool_types');
    }

    /**
     * Run the migrations.
     *
     * Catálogo per-game de tipos de bote (Bote Fijo/Acumulativo/Mixto/Premio).
     * Cada tipo pertenece a un único juego (game_id NOT NULL) — no se
     * comparten entre juegos.
     */
    public function up() : void
    {
        Schema::create('prize_pool_types', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del tipo de premio');

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete()
                ->comment('Juego al que pertenece este tipo de bote');

            $table->string('code', 30)
                ->comment('Código único del tipo de Pool');

            $table->string('label', 50)->comment('Etiqueta legible del tipo de premio');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            $table->unique(['game_id', 'code'], 'uniq_prize_pool_types_game_code');
            $table->index('status', 'idx_prize_pool_types_status');
        });
    }
};
