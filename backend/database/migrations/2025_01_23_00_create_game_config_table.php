<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_configs', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->cascadeOnDelete();

            $table->string('param', 50); // Nombre del parámetro
            $table->string('value', 100)->nullable(); // Valor del parámetro
            $table->string('description', 150)->nullable();

            $table->boolean('status')
                ->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Evita duplicar parámetros por juego
            $table->unique(['game_id', 'param'], 'unique_game_param');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_configs');
    }
};
