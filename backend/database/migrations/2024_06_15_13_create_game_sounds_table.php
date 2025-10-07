<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla 'game_sounds'.
     */
    public function up(): void
    {
        Schema::create('game_sounds', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID del sonido del juego');

            $table->string('file', 50)
                ->unique()
                ->comment('Nombre del archivo de sonido (ej. mp3, wav)');

            $table->string('description', 100)
                ->nullable()
                ->comment('Descripción o uso del sonido');

            $table->boolean('is_active')
                ->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();
        });
    }

    /**
     * Revierte la migración: elimina la tabla 'game_sounds'.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sounds');
    }
};
