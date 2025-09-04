<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('description', 50);
            $table->string('code', 10)->index();

            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();

            $table->boolean('is_active')->default(true)
                ->comment('true = activo, false = inactivo');

            $table->timestamps();

            // Evitar duplicados de código por juego
            $table->unique(['game_id', 'code'], 'unique_game_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_prizes');
    }
};
