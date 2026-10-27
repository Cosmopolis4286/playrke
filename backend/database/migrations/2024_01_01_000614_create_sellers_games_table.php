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
        Schema::dropIfExists('sellers_games');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('sellers_games', function(Blueprint $table) {
            // Vendedor
            $table->foreignSnowflake('seller_id')
                ->constrained('sellers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Vendedor asociado');
            // Juego
            $table->foreignSnowflake('game_id')->constrained('games')->onDelete('cascade');
            // Estado
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])
                ->default('ACTIVE')
                ->comment('Estado del registro');
            // Timestamps automáticos
            $table->timestamps();
            // Soft deletes
            $table->softDeletes();

            // Evita duplicados: un vendedor solo puede tener un registro por juego
            $table->unique(['seller_id', 'game_id'], 'seller_game_unique');
        });
    }
};
