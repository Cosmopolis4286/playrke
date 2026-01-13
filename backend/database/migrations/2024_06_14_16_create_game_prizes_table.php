<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'game_prizes'.
     */
    public function up(): void
    {
        Schema::create('game_prizes', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único del premio');
            
            $table->string('description', 50)->comment('Descripción legible del premio');
            $table->string('code', 10)->comment('Código único interno del premio')->index();

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete()
                ->comment('Juego asociado al premio');

            $table->boolean('status')
                ->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Restricción única para evitar duplicados por juego
            $table->unique(['game_id', 'code'], 'unique_game_code');
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'game_prizes'.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_prizes');
    }
};
