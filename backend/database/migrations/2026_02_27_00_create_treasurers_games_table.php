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
        Schema::create('treasurers_games', function (Blueprint $table) {
            // Tesorero
            $table->foreignSnowflake('treasurer_id')
                ->constrained('treasurers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Tesorero asociado');
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

            // Evita duplicados: un comercio solo puede tener un registro por juego
            $table->unique(['treasurer_id', 'game_id'], 'treasurer_game_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasurers_games');
    }
};
